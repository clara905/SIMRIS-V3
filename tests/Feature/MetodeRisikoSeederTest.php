<?php

namespace Tests\Feature;

use App\Models\MatriksRisiko;
use App\Models\SkalaDampak;
use App\Models\SkalaKemungkinan;
use App\Models\TingkatRisiko;
use App\Models\VersiMetodeRisiko;
use Database\Seeders\MetodeRisikoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MetodeRisikoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_method_version_and_all_master_data(): void
    {
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where(
            'kode_versi',
            'SIMRIS-MR-001'
        )->firstOrFail();

        $this->assertSame(5, SkalaKemungkinan::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, SkalaDampak::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, TingkatRisiko::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(25, MatriksRisiko::where('versi_metode_id', $versi->id)->count());
    }

    public function test_seeder_stores_probability_scales_correctly(): void
    {
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->firstOrFail();

        $this->assertSame(
            'Hampir Tidak Terjadi',
            SkalaKemungkinan::where('versi_metode_id', $versi->id)
                ->where('nilai', 1)
                ->value('nama')
        );

        $this->assertSame(
            'Hampir Pasti Terjadi',
            SkalaKemungkinan::where('versi_metode_id', $versi->id)
                ->where('nilai', 5)
                ->value('nama')
        );
    }

    public function test_seeder_stores_impact_scales_correctly(): void
    {
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->firstOrFail();

        $this->assertSame(
            'Tidak Signifikan',
            SkalaDampak::where('versi_metode_id', $versi->id)
                ->where('nilai', 1)
                ->value('nama')
        );

        $this->assertSame(
            'Sangat Signifikan',
            SkalaDampak::where('versi_metode_id', $versi->id)
                ->where('nilai', 5)
                ->value('nama')
        );
    }

    public function test_seeder_stores_exact_matrix_scores_and_decisions(): void
    {
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->firstOrFail();

        $kemungkinan = SkalaKemungkinan::where('versi_metode_id', $versi->id)
            ->where('nilai', 5)
            ->firstOrFail();

        $dampak = SkalaDampak::where('versi_metode_id', $versi->id)
            ->where('nilai', 4)
            ->firstOrFail();

        $matriks = MatriksRisiko::where('versi_metode_id', $versi->id)
            ->where('kemungkinan_id', $kemungkinan->id)
            ->where('dampak_id', $dampak->id)
            ->firstOrFail();

        $this->assertSame(23, $matriks->skor);
        $this->assertSame('MODIFIKASI', $matriks->rekomendasi_keputusan);

        $kemungkinanTerima = SkalaKemungkinan::where('versi_metode_id', $versi->id)
            ->where('nilai', 1)
            ->firstOrFail();

        $dampakTerima = SkalaDampak::where('versi_metode_id', $versi->id)
            ->where('nilai', 3)
            ->firstOrFail();

        $matriksTerima = MatriksRisiko::where('versi_metode_id', $versi->id)
            ->where('kemungkinan_id', $kemungkinanTerima->id)
            ->where('dampak_id', $dampakTerima->id)
            ->firstOrFail();

        $this->assertSame(5, $matriksTerima->skor);
        $this->assertSame('TERIMA', $matriksTerima->rekomendasi_keputusan);
    }

    public function test_seeder_can_be_executed_repeatedly_without_duplicates(): void
    {
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where(
            'kode_versi',
            'SIMRIS-MR-001'
        )->firstOrFail();
        $kemungkinan = SkalaKemungkinan::query()
            ->where('versi_metode_id', $versi->id)
            ->where('nilai', 1)
            ->firstOrFail();
        $matriks = MatriksRisiko::query()
            ->where('versi_metode_id', $versi->id)
            ->where('kemungkinan_id', $kemungkinan->id)
            ->whereHas('dampak', fn ($query) => $query->where('nilai', 1))
            ->firstOrFail();

        $kemungkinan->update(['nama' => 'Label draft khusus']);
        $matriks->update(['rekomendasi_keputusan' => 'MODIFIKASI']);

        $this->seed(MetodeRisikoSeeder::class);

        $this->assertSame(1, VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->count());
        $this->assertSame(5, SkalaKemungkinan::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, SkalaDampak::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, TingkatRisiko::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(25, MatriksRisiko::where('versi_metode_id', $versi->id)->count());
        $this->assertSame('Label draft khusus', $kemungkinan->fresh()->nama);
        $this->assertSame('MODIFIKASI', $matriks->fresh()->rekomendasi_keputusan);
    }

    public function test_seeder_rolls_back_master_rows_when_matrix_creation_fails(): void
    {
        DB::unprepared("
            CREATE TRIGGER fail_matrix_seed
            BEFORE INSERT ON matriks_risiko
            BEGIN
                SELECT RAISE(FAIL, 'matrix seeder failure');
            END
        ");

        try {
            try {
                $this->seed(MetodeRisikoSeeder::class);
                $this->fail('The method seeder should fail when inserting a matrix row.');
            } catch (QueryException) {
                $this->assertDatabaseMissing('versi_metode_risiko', [
                    'kode_versi' => 'SIMRIS-MR-001',
                ]);
                $this->assertDatabaseCount('skala_kemungkinan', 0);
                $this->assertDatabaseCount('skala_dampak', 0);
                $this->assertDatabaseCount('tingkat_risiko', 0);
                $this->assertDatabaseCount('matriks_risiko', 0);
            }
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS fail_matrix_seed');
        }
    }

    public function test_seeder_refuses_to_modify_frozen_method_version(): void
    {
        $versi = VersiMetodeRisiko::create([
            'kode_versi' => 'SIMRIS-MR-001',
            'nama' => 'Metode yang sudah dibekukan',
            'deskripsi' => 'Data harus tetap terlindungi.',
            'dibekukan' => true,
            'dibekukan_pada' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sudah dibekukan');

        try {
            $this->seed(MetodeRisikoSeeder::class);
        } finally {
            $this->assertSame(
                'Metode yang sudah dibekukan',
                $versi->fresh()->nama
            );
        }
    }
}
