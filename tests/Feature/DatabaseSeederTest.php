<?php

namespace Tests\Feature;

use App\Models\MatriksRisiko;
use App\Models\Role;
use App\Models\SkalaKemungkinan;
use App\Models\SumberAncaman;
use App\Models\TingkatRisiko;
use App\Models\VersiMetodeRisiko;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_is_repeatable_and_preserves_existing_data(): void
    {
        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => null,
            'username' => 'seeder-test-user',
            'email' => 'seeder-test@example.test',
            'firebase_uid' => 'seeder-test-firebase',
            'aktif' => true,
        ]);

        DB::table('roles')->insert([
            'kode' => 'SUPER_ADMIN',
            'nama' => 'Nama role yang dikelola pengguna',
        ]);

        $accountBeforeSeeders = (array) DB::table('users')
            ->where('id', $userId)
            ->first();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('sumber_ancaman', [
            'kode' => 'S',
            'nama' => 'Sengaja',
        ]);
        $this->assertDatabaseHas('sumber_ancaman', [
            'kode' => 'TS',
            'nama' => 'Tidak Sengaja',
        ]);
        $this->assertDatabaseHas('sumber_ancaman', [
            'kode' => 'L',
            'nama' => 'Lingkungan',
        ]);

        $versi = VersiMetodeRisiko::query()
            ->where('kode_versi', 'SIMRIS-MR-001')
            ->firstOrFail();
        $kemungkinan = SkalaKemungkinan::query()
            ->where('versi_metode_id', $versi->id)
            ->where('nilai', 1)
            ->firstOrFail();
        $matriks = MatriksRisiko::query()
            ->where('versi_metode_id', $versi->id)
            ->where('kemungkinan_id', $kemungkinan->id)
            ->whereHas('dampak', fn ($query) => $query->where('nilai', 1))
            ->firstOrFail();

        $versi->update(['nama' => 'Nama metode yang dikelola pengguna']);
        $kemungkinan->update(['nama' => 'Label kemungkinan yang dikelola pengguna']);
        $matriks->update(['rekomendasi_keputusan' => 'MODIFIKASI']);
        TingkatRisiko::query()
            ->where('versi_metode_id', $versi->id)
            ->where('kode', 'SANGAT_RENDAH')
            ->update(['nama' => 'Label tingkat yang dikelola pengguna']);
        SumberAncaman::query()
            ->where('kode', 'S')
            ->update(['nama' => 'Sumber yang dikelola pengguna']);

        $countsBeforeSecondRun = [
            'roles' => Role::query()->count(),
            'versions' => VersiMetodeRisiko::query()->count(),
            'probability_scales' => SkalaKemungkinan::query()->count(),
            'impact_scales' => DB::table('skala_dampak')->count(),
            'risk_levels' => TingkatRisiko::query()->count(),
            'matrices' => MatriksRisiko::query()->count(),
            'threat_sources' => SumberAncaman::query()->count(),
        ];
        $this->seed(DatabaseSeeder::class);

        $countsAfterSecondRun = [
            'roles' => Role::query()->count(),
            'versions' => VersiMetodeRisiko::query()->count(),
            'probability_scales' => SkalaKemungkinan::query()->count(),
            'impact_scales' => DB::table('skala_dampak')->count(),
            'risk_levels' => TingkatRisiko::query()->count(),
            'matrices' => MatriksRisiko::query()->count(),
            'threat_sources' => SumberAncaman::query()->count(),
        ];

        $this->assertSame([
            'roles' => 6,
            'versions' => 1,
            'probability_scales' => 5,
            'impact_scales' => 5,
            'risk_levels' => 5,
            'matrices' => 25,
            'threat_sources' => 3,
        ], $countsBeforeSecondRun);
        $this->assertSame($countsBeforeSecondRun, $countsAfterSecondRun);
        $this->assertSame(
            $accountBeforeSeeders,
            (array) DB::table('users')->where('id', $userId)->first()
        );
        $this->assertSame(
            'Nama role yang dikelola pengguna',
            Role::query()->where('kode', 'SUPER_ADMIN')->value('nama')
        );
        $this->assertSame(
            'Nama metode yang dikelola pengguna',
            $versi->fresh()->nama
        );
        $this->assertSame(
            'Label kemungkinan yang dikelola pengguna',
            $kemungkinan->fresh()->nama
        );
        $this->assertSame(
            'MODIFIKASI',
            $matriks->fresh()->rekomendasi_keputusan
        );
        $this->assertSame(
            'Label tingkat yang dikelola pengguna',
            TingkatRisiko::query()
                ->where('versi_metode_id', $versi->id)
                ->where('kode', 'SANGAT_RENDAH')
                ->value('nama')
        );
        $this->assertSame(
            'Sumber yang dikelola pengguna',
            SumberAncaman::query()->where('kode', 'S')->value('nama')
        );
        $this->assertDatabaseHas('sumber_ancaman', ['kode' => 'TS']);
        $this->assertDatabaseHas('sumber_ancaman', ['kode' => 'L']);
    }
}
