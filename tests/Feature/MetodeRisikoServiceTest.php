<?php

namespace Tests\Feature;

use App\Models\VersiMetodeRisiko;
use App\Services\AuditService;
use App\Services\MetodeRisikoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MetodeRisikoServiceTest extends TestCase
{
    use RefreshDatabase;

    private MetodeRisikoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MetodeRisikoService::class);
    }

    public function test_create_draft_creates_unfrozen_method_and_audit(): void
    {
        $versi = $this->service->createDraft([
            'kode_versi' => 'METODE-2026-01',
            'nama' => 'Metode Risiko 2026',
            'deskripsi' => 'Metode pengujian.',
        ]);

        $this->assertFalse($versi->dibekukan);
        $this->assertNull($versi->dibekukan_pada);

        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $versi->id,
            'kode_versi' => 'METODE-2026-01',
            'dibekukan' => false,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CREATE_DRAFT_METODE_RISIKO',
            'entitas' => 'versi_metode_risiko',
            'entitas_id' => $versi->id,
        ]);
    }

    public function test_configure_draft_creates_all_scales_levels_and_matrix_rows(): void
    {
        $versi = $this->createDraft();

        $configured = $this->service->configureDraft(
            $versi,
            $this->validConfiguration(),
        );

        $this->assertSame(5, $configured->skalaKemungkinan->count());
        $this->assertSame(5, $configured->skalaDampak->count());
        $this->assertSame(3, $configured->tingkatRisiko->count());
        $this->assertSame(25, $configured->matriksRisiko->count());

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CONFIGURE_METODE_RISIKO',
            'entitas_id' => $versi->id,
        ]);
    }

    public function test_configuration_rejects_missing_matrix_combination(): void
    {
        $versi = $this->createDraft();
        $configuration = $this->validConfiguration();

        array_pop($configuration['matriks']);

        $this->expectException(ValidationException::class);

        $this->service->configureDraft($versi, $configuration);
    }

    public function test_configuration_rejects_duplicate_matrix_combination(): void
    {
        $versi = $this->createDraft();
        $configuration = $this->validConfiguration();

        $configuration['matriks'][24]['kemungkinan_nilai'] = 1;
        $configuration['matriks'][24]['dampak_nilai'] = 1;

        $this->expectException(ValidationException::class);

        $this->service->configureDraft($versi, $configuration);
    }

    public function test_freeze_requires_complete_configuration(): void
    {
        $versi = $this->createDraft();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Metode harus memiliki tepat 5 skala kemungkinan dan 5 skala dampak.'
        );

        $this->service->freeze($versi);
    }

    public function test_freeze_accepts_complete_configuration(): void
    {
        $versi = $this->createDraft();

        $this->service->configureDraft(
            $versi,
            $this->validConfiguration(),
        );

        $frozen = $this->service->freeze($versi);

        $this->assertTrue($frozen->dibekukan);
        $this->assertNotNull($frozen->dibekukan_pada);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'FREEZE_METODE_RISIKO',
            'entitas' => 'versi_metode_risiko',
            'entitas_id' => $versi->id,
        ]);
    }

    public function test_frozen_method_cannot_be_configured_or_frozen_again(): void
    {
        $versi = $this->createDraft();

        $this->service->configureDraft(
            $versi,
            $this->validConfiguration(),
        );

        $frozen = $this->service->freeze($versi);

        try {
            $this->service->configureDraft(
                $frozen,
                $this->validConfiguration(),
            );

            $this->fail('Metode beku tidak boleh dikonfigurasi ulang.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Metode risiko sudah dibekukan dan tidak dapat diubah.',
                $exception->getMessage()
            );
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Metode risiko sudah dibekukan dan tidak dapat diubah.'
        );

        $this->service->freeze($frozen);
    }

    public function test_freeze_rejects_matrix_reference_from_another_method_version(): void
    {
        $first = $this->createDraft('METODE-A');
        $second = $this->createDraft('METODE-B');

        $this->service->configureDraft(
            $first,
            $this->validConfiguration(),
        );

        $this->service->configureDraft(
            $second,
            $this->validConfiguration(),
        );

        $foreignScale = $second->skalaKemungkinan()->firstOrFail();

        $matrix = $first->matriksRisiko()->firstOrFail();

        // FK mengizinkan referensi ini, tetapi aturan domain harus menolaknya.
        $matrix->forceFill([
            'kemungkinan_id' => $foreignScale->id,
        ])->save();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Matriks memiliki referensi yang tidak valid atau berbeda versi.'
        );

        $this->service->freeze($first);
    }

    public function test_configuration_is_rolled_back_when_audit_fails(): void
    {
        $versi = $this->createDraft();

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit konfigurasi gagal.'));

        $this->app->instance(AuditService::class, $auditService);

        try {
            app(MetodeRisikoService::class)->configureDraft(
                $versi,
                $this->validConfiguration(),
            );

            $this->fail('Konfigurasi seharusnya dibatalkan ketika audit gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit konfigurasi gagal.',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            0,
            $versi->skalaKemungkinan()->count()
        );

        $this->assertSame(
            0,
            $versi->skalaDampak()->count()
        );

        $this->assertSame(
            0,
            $versi->tingkatRisiko()->count()
        );

        $this->assertSame(
            0,
            $versi->matriksRisiko()->count()
        );
    }

    private function createDraft(
        string $kode = 'METODE-TEST',
    ): VersiMetodeRisiko {
        return $this->service->createDraft([
            'kode_versi' => $kode,
            'nama' => 'Metode Pengujian',
            'deskripsi' => 'Metode untuk feature test.',
        ]);
    }

    private function validConfiguration(): array
    {
        $kemungkinan = [];
        $dampak = [];

        for ($nilai = 1; $nilai <= 5; $nilai++) {
            $kemungkinan[] = [
                'nilai' => $nilai,
                'nama' => 'Kemungkinan '.$nilai,
                'deskripsi' => 'Deskripsi kemungkinan '.$nilai,
            ];

            $dampak[] = [
                'nilai' => $nilai,
                'nama' => 'Dampak '.$nilai,
                'deskripsi' => 'Deskripsi dampak '.$nilai,
            ];
        }

        $matriks = [];

        for ($k = 1; $k <= 5; $k++) {
            for ($d = 1; $d <= 5; $d++) {
                $matriks[] = [
                    'kemungkinan_nilai' => $k,
                    'dampak_nilai' => $d,
                    'tingkat_risiko_kode' => $this->levelCode($k + $d),
                    'skor' => ($k * 10) + $d,
                    'rekomendasi_keputusan' => 'Rekomendasi '.$k.'-'.$d,
                ];
            }
        }

        return [
            'kemungkinan' => $kemungkinan,
            'dampak' => $dampak,
            'tingkat_risiko' => [
                [
                    'kode' => 'RENDAH',
                    'nama' => 'Rendah',
                    'urutan' => 1,
                ],
                [
                    'kode' => 'SEDANG',
                    'nama' => 'Sedang',
                    'urutan' => 2,
                ],
                [
                    'kode' => 'TINGGI',
                    'nama' => 'Tinggi',
                    'urutan' => 3,
                ],
            ],
            'matriks' => $matriks,
        ];
    }

    private function levelCode(int $sum): string
    {
        return match (true) {
            $sum <= 4 => 'RENDAH',
            $sum <= 7 => 'SEDANG',
            default => 'TINGGI',
        };
    }
}
