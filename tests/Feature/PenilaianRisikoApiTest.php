<?php

namespace Tests\Feature;

use App\Models\Ancaman;
use App\Models\AsesmenRisiko;
use App\Models\JenisAncaman;
use App\Models\JenisKerentanan;
use App\Models\Kerentanan;
use App\Models\MatriksRisiko;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PeriodePenilaian;
use App\Models\Risiko;
use App\Models\Role;
use App\Models\SeriAsesmen;
use App\Models\SumberAncaman;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Models\VersiMetodeRisiko;
use App\Services\MetodeRisikoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PenilaianRisikoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_risk_assessment_api(): void
    {
        $this->getJson('/api/v1/risiko/1/penilaian')
            ->assertUnauthorized();

        $this->postJson('/api/v1/risiko/1/penilaian', [])
            ->assertUnauthorized();
    }

    public function test_staff_can_create_inherent_and_residual_assessments(): void
    {
        $fixture = $this->createRiskFixture();

        Sanctum::actingAs($fixture['user']);

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload($fixture['matriks']->id, 'INHEREN')
        )
            ->assertCreated()
            ->assertJsonPath('data.jenis_penilaian', 'INHEREN')
            ->assertJsonPath(
                'data.penilai.id',
                $fixture['user']->id
            );

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload($fixture['matriks']->id, 'RESIDUAL')
        )
            ->assertCreated()
            ->assertJsonPath('data.jenis_penilaian', 'RESIDUAL');

        $this->assertDatabaseCount('penilaian_risiko', 2);

        $this->assertDatabaseHas('penilaian_risiko', [
            'risiko_id' => $fixture['risiko']->id,
            'dinilai_oleh' => $fixture['user']->id,
            'jenis_penilaian' => 'INHEREN',
        ]);

        $this->assertDatabaseHas('penilaian_risiko', [
            'risiko_id' => $fixture['risiko']->id,
            'dinilai_oleh' => $fixture['user']->id,
            'jenis_penilaian' => 'RESIDUAL',
        ]);
    }

    public function test_client_cannot_spoof_assessor_identity(): void
    {
        $fixture = $this->createRiskFixture();

        $otherUser = $this->createStaffUser(
            $fixture['unit'],
            'pegawai-palsu'
        );

        Sanctum::actingAs($fixture['user']);

        $payload = $this->validAssessmentPayload(
            $fixture['matriks']->id,
            'INHEREN'
        );

        $payload['dinilai_oleh'] = $otherUser->id;

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $payload
        )->assertCreated();

        $this->assertDatabaseHas('penilaian_risiko', [
            'risiko_id' => $fixture['risiko']->id,
            'dinilai_oleh' => $fixture['user']->id,
        ]);

        $this->assertDatabaseMissing('penilaian_risiko', [
            'risiko_id' => $fixture['risiko']->id,
            'dinilai_oleh' => $otherUser->id,
        ]);
    }

    public function test_staff_cannot_assess_risk_from_another_unit(): void
    {
        $fixture = $this->createRiskFixture();

        $otherUnit = $this->createUnit('UNIT-LAIN');

        $otherFixture = $this->createRiskFixture(
            $otherUnit,
            $fixture['user']
        );

        Sanctum::actingAs($fixture['user']);

        $this->postJson(
            "/api/v1/risiko/{$otherFixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload(
                $otherFixture['matriks']->id,
                'INHEREN'
            )
        )->assertForbidden();

        $this->assertDatabaseCount('penilaian_risiko', 0);
    }

    public function test_kasub_cannot_create_risk_assessment(): void
    {
        $fixture = $this->createRiskFixture();

        $kasub = $this->createUnitRoleUser(
            'KASUB',
            $fixture['unit']
        );

        Sanctum::actingAs($kasub);

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload(
                $fixture['matriks']->id,
                'INHEREN'
            )
        )->assertForbidden();

        $this->assertDatabaseCount('penilaian_risiko', 0);
    }

    public function test_assessment_cannot_be_created_after_submission(): void
    {
        $fixture = $this->createRiskFixture();

        $fixture['asesmen']->forceFill([
            'status' => 'DIAJUKAN',
        ])->save();

        Sanctum::actingAs($fixture['user']);

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload(
                $fixture['matriks']->id,
                'INHEREN'
            )
        )->assertStatus(409);

        $this->assertDatabaseCount('penilaian_risiko', 0);
    }

    public function test_assessment_history_is_preserved_when_new_assessment_is_created(): void
    {
        $fixture = $this->createRiskFixture();

        Sanctum::actingAs($fixture['user']);

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload(
                $fixture['matriks']->id,
                'INHEREN'
            )
        )->assertCreated();

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $this->validAssessmentPayload(
                $fixture['matriks']->id,
                'RESIDUAL'
            )
        )->assertCreated();

        $this->getJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian"
        )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'jenis_penilaian' => 'INHEREN',
            ])
            ->assertJsonFragment([
                'jenis_penilaian' => 'RESIDUAL',
            ]);
    }

    public function test_invalid_assessment_type_is_rejected(): void
    {
        $fixture = $this->createRiskFixture();

        Sanctum::actingAs($fixture['user']);

        $payload = $this->validAssessmentPayload(
            $fixture['matriks']->id,
            'INHEREN'
        );

        $payload['jenis_penilaian'] = 'FINAL';

        $this->postJson(
            "/api/v1/risiko/{$fixture['risiko']->id}/penilaian",
            $payload
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jenis_penilaian');

        $this->assertDatabaseCount('penilaian_risiko', 0);
    }

    private function createRiskFixture(
        ?UnitOrganisasi $unit = null,
        ?User $creator = null,
    ): array {
        $unit ??= $this->createUnit('UNIT-UTAMA');

        $user = $creator ?? $this->createStaffUser($unit);

        $versiMetode = $this->createFrozenMethod();

        $seri = SeriAsesmen::create([
            'kode' => fake()->unique()->bothify('SERI-####'),
            'nama' => 'Seri Asesmen Pengujian',
        ]);

        $periode = PeriodePenilaian::create([
            'unit_id' => $unit->id,
            'dibuat_oleh' => $user->id,
            'nama_periode' => 'Periode Pengujian',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonth()->toDateString(),
            'status' => 'DRAFT',
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ]);

        $asesmen = AsesmenRisiko::create([
            'seri_asesmen_id' => $seri->id,
            'versi_sebelumnya_id' => null,
            'periode_id' => $periode->id,
            'unit_id' => $unit->id,
            'versi_metode_id' => $versiMetode->id,
            'dibuat_oleh' => $user->id,
            'tujuan' => 'Pengujian penilaian risiko.',
            'ruang_lingkup' => 'Ruang lingkup pengujian.',
            'status' => 'DRAFT',
            'nomor_versi' => 1,
            'dibuat_pada' => now(),
            'dibekukan_pada' => null,
        ]);

        $jenisAncaman = JenisAncaman::create([
            'kode' => fake()->unique()->bothify('JA-####'),
            'nama' => 'Jenis Ancaman Pengujian',
        ]);

        $ancaman = Ancaman::create([
            'jenis_ancaman_id' => $jenisAncaman->id,
            'kode' => fake()->unique()->bothify('ANC-####'),
            'nama' => 'Ancaman Pengujian',
            'deskripsi' => 'Deskripsi ancaman pengujian.',
        ]);

        $jenisKerentanan = JenisKerentanan::create([
            'kode' => fake()->unique()->bothify('JK-####'),
            'nama' => 'Jenis Kerentanan Pengujian',
        ]);

        $kerentanan = Kerentanan::create([
            'jenis_kerentanan_id' => $jenisKerentanan->id,
            'kode' => fake()->unique()->bothify('KER-####'),
            'nama' => 'Kerentanan Pengujian',
            'deskripsi' => 'Deskripsi kerentanan pengujian.',
        ]);

        $sumberAncaman = SumberAncaman::create([
            'kode' => fake()->unique()->bothify('SA-####'),
            'nama' => 'Sumber Ancaman Pengujian',
        ]);

        $pemilikRisiko = $user->pegawai_id
            ? Pegawai::findOrFail($user->pegawai_id)
            : Pegawai::create([
                'nip' => fake()->unique()->numerify('198#########'),
                'nama' => 'Pemilik Risiko Pengujian',
                'email_kontak' => null,
                'jenis_kepegawaian' => 'PNS',
                'aktif' => true,
            ]);

        $risiko = Risiko::create([
            'asesmen_id' => $asesmen->id,
            'ancaman_id' => $ancaman->id,
            'sumber_ancaman_id' => $sumberAncaman->id,
            'kerentanan_id' => $kerentanan->id,
            'pemilik_risiko_id' => $pemilikRisiko->id,
            'dibuat_oleh' => $user->id,
            'kode' => fake()->unique()->bothify('RSK-####'),
            'skenario' => 'Skenario risiko pengujian.',
            'uraian_kerentanan' => 'Uraian kerentanan pengujian.',
            'uraian_dampak' => 'Uraian dampak pengujian.',
            'arah_dampak' => true,
            'dibuat_pada' => now(),
        ]);

        $matriks = MatriksRisiko::query()
            ->where('versi_metode_id', $versiMetode->id)
            ->firstOrFail();

        return [
            'user' => $user,
            'unit' => $unit,
            'versi_metode' => $versiMetode,
            'asesmen' => $asesmen,
            'risiko' => $risiko,
            'matriks' => $matriks,
        ];
    }

    private function createFrozenMethod(): VersiMetodeRisiko
    {
        $service = app(MetodeRisikoService::class);

        $versi = $service->createDraft([
            'kode_versi' => fake()->unique()->bothify('METODE-####'),
            'nama' => 'Metode Pengujian',
            'deskripsi' => 'Metode untuk pengujian penilaian risiko.',
        ]);

        $service->configureDraft(
            $versi,
            $this->validConfiguration()
        );

        return $service->freeze($versi);
    }

    private function createStaffUser(
        UnitOrganisasi $unit,
        string $suffix = 'staff',
    ): User {
        $pegawai = Pegawai::create([
            'nip' => fake()->unique()->numerify('199#########'),
            'nama' => 'Staff Pengujian '.$suffix,
            'email_kontak' => null,
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);

        $user = User::create([
            'pegawai_id' => $pegawai->id,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        $role = Role::where('kode', 'STAFF')->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => null,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => now()->subDay()->toDateString(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function createUnit(string $code): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => $code.'-'.fake()->unique()->numerify('####'),
            'nama' => 'Unit Pengujian '.$code,
            'jenis' => 'SUBBAGIAN',
            'aktif' => true,
        ]);
    }

    private function createUnitRoleUser(
        string $roleCode,
        UnitOrganisasi $unit,
    ): User {
        $user = User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unit->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function validAssessmentPayload(
        int $matriksId,
        string $type,
    ): array {
        return [
            'matriks_id' => $matriksId,
            'jenis_penilaian' => $type,
            'alasan_kemungkinan' => 'Alasan kemungkinan pengujian.',
            'alasan_dampak' => 'Alasan dampak pengujian.',
            'kesimpulan' => 'Kesimpulan penilaian risiko pengujian.',
        ];
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
