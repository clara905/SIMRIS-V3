<?php

namespace Tests\Feature;

use App\Models\MatriksRisiko;
use App\Models\SkalaDampak;
use App\Models\SkalaKemungkinan;
use App\Models\TingkatRisiko;
use App\Models\VersiMetodeRisiko;
use Database\Seeders\MetodeRisikoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->seed(MetodeRisikoSeeder::class);

        $versi = VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->firstOrFail();

        $this->assertSame(1, VersiMetodeRisiko::where('kode_versi', 'SIMRIS-MR-001')->count());
        $this->assertSame(5, SkalaKemungkinan::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, SkalaDampak::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(5, TingkatRisiko::where('versi_metode_id', $versi->id)->count());
        $this->assertSame(25, MatriksRisiko::where('versi_metode_id', $versi->id)->count());
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
