<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenempatanPegawaiApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASUB',
        ] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_placement_endpoints(): void
    {
        $pegawai = $this->createPegawai();

        $this->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [])
            ->assertUnauthorized();

        $this->getJson("/api/v1/pegawai/{$pegawai->id}/riwayat-penempatan")
            ->assertUnauthorized();
    }

    public function test_super_admin_can_transfer_pegawai(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $oldUnit = $this->createUnit('SATKER');
        $newUnit = $this->createUnit('SATKER');

        $pegawai = $this->createPegawai();
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            today()->subMonth()->toDateString()
        );

        $effectiveDate = today()->toDateString();

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [
                'unit_tujuan_id' => $newUnit->id,
                'tanggal_efektif' => $effectiveDate,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Penempatan pegawai berhasil dipindahkan.')
            ->assertJsonPath('data.penempatan_baru.unit_id', $newUnit->id)
            ->assertJsonPath('data.penempatan_baru.mulai', $effectiveDate);

        $this->assertTrue(
            PenempatanPegawai::query()
                ->whereKey($oldPlacement->id)
                ->whereDate('selesai', today()->subDay())
                ->exists()
        );

        $this->assertTrue(
            PenempatanPegawai::query()
                ->where('pegawai_id', $pegawai->id)
                ->where('unit_id', $newUnit->id)
                ->whereDate('mulai', $effectiveDate)
                ->whereNull('selesai')
                ->exists()
        );

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CHANGE_PLACEMENT',
            'entitas' => 'penempatan_pegawai',
        ]);
    }

    public function test_admin_satker_can_transfer_pegawai_within_own_scope(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $this->assignRole($user, 'ADMIN_SATKER', $satker->id);

        $pegawai = $this->createPegawai();
        $this->createPlacement(
            $pegawai,
            $bidang,
            today()->subMonth()->toDateString()
        );

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [
                'unit_tujuan_id' => $subbidang->id,
                'tanggal_efektif' => today()->toDateString(),
            ])
            ->assertOk();

        $this->assertDatabaseHas('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $subbidang->id,
            'selesai' => null,
        ]);
    }

    public function test_admin_satker_cannot_transfer_pegawai_outside_scope(): void
    {
        $user = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');
        $bidangA = $this->createUnit('BIDANG', $satkerA->id);
        $bidangB = $this->createUnit('BIDANG', $satkerB->id);

        $this->assignRole($user, 'ADMIN_SATKER', $satkerA->id);

        $pegawai = $this->createPegawai();
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $bidangA,
            today()->subMonth()->toDateString()
        );

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [
                'unit_tujuan_id' => $bidangB->id,
                'tanggal_efektif' => today()->toDateString(),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacement->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $bidangB->id,
        ]);
    }

    public function test_kasub_cannot_transfer_pegawai(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit('SUBBIDANG');
        $destination = $this->createUnit('SUBBIDANG');

        $this->assignRole($user, 'KASUB', $unit->id);

        $pegawai = $this->createPegawai();
        $this->createPlacement(
            $pegawai,
            $unit,
            today()->subMonth()->toDateString()
        );

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [
                'unit_tujuan_id' => $destination->id,
                'tanggal_efektif' => today()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_transfer_rejects_invalid_effective_date(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $oldUnit = $this->createUnit('SATKER');
        $newUnit = $this->createUnit('SATKER');

        $pegawai = $this->createPegawai();

        $oldStart = today()->subMonth();
        $this->createPlacement(
            $pegawai,
            $oldUnit,
            $oldStart->toDateString()
        );

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [
                'unit_tujuan_id' => $newUnit->id,
                'tanggal_efektif' => $oldStart->toDateString(),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);
    }

    public function test_super_admin_can_view_all_placement_history(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $unitA = $this->createUnit('SATKER');
        $unitB = $this->createUnit('SATKER');

        $pegawai = $this->createPegawai();

        $this->createPlacement(
            $pegawai,
            $unitA,
            today()->subMonths(2)->toDateString(),
            today()->subMonth()->toDateString()
        );

        $this->createPlacement(
            $pegawai,
            $unitB,
            today()->subMonth()->toDateString()
        );

        $this->actingAs($user)
            ->getJson("/api/v1/pegawai/{$pegawai->id}/riwayat-penempatan")
            ->assertOk()
            ->assertJsonCount(2, 'data.riwayat_penempatan');
    }

    public function test_admin_satker_only_sees_placement_history_within_scope(): void
    {
        $user = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $unitA = $this->createUnit('BIDANG', $satkerA->id);
        $unitB = $this->createUnit('BIDANG', $satkerB->id);

        $this->assignRole($user, 'ADMIN_SATKER', $satkerA->id);

        $pegawai = $this->createPegawai();

        $this->createPlacement(
            $pegawai,
            $unitB,
            today()->subMonths(2)->toDateString(),
            today()->subMonth()->toDateString()
        );

        $this->createPlacement(
            $pegawai,
            $unitA,
            today()->toDateString()
        );

        $response = $this->actingAs($user)
            ->getJson("/api/v1/pegawai/{$pegawai->id}/riwayat-penempatan")
            ->assertOk()
            ->assertJsonCount(1, 'data.riwayat_penempatan');

        $this->assertSame(
            $unitA->id,
            $response->json('data.riwayat_penempatan.0.unit_id')
        );
    }

    public function test_transfer_rejects_missing_required_fields(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $pegawai = $this->createPegawai();

        $this->actingAs($user)
            ->patchJson("/api/v1/pegawai/{$pegawai->id}/transfer", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'unit_tujuan_id',
                'tanggal_efektif',
            ]);
    }

    private function createUser(): User
    {
        return User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);
    }

    private function createPegawai(): Pegawai
    {
        return Pegawai::create([
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);
    }

    private function createUnit(
        string $jenis,
        ?int $parentId = null
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => $parentId,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
            'aktif' => true,
        ]);
    }

    private function createPlacement(
        Pegawai $pegawai,
        UnitOrganisasi $unit,
        ?string $mulai = null,
        ?string $selesai = null
    ): PenempatanPegawai {
        return PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => $mulai ?? today()->subDay()->toDateString(),
            'selesai' => $selesai,
        ]);
    }

    private function assignRole(
        User $user,
        string $roleCode,
        ?int $unitId = null
    ): void {
        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
