<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\PenempatanPegawaiService;
use App\Services\RoleScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RoleScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleScopeService::class);
    }

    public function test_super_admin_has_no_specific_unit_scope(): void
    {
        $role = $this->createRole('SUPER_ADMIN');
        $user = $this->createUser();

        $this->createScope($user, $role, null);

        $this->assertSame(
            'SUPER_ADMIN',
            $this->service->getActiveRoleCode($user)
        );

        $this->assertNull(
            $this->service->getEffectiveUnitId($user)
        );
    }

    public function test_staff_uses_active_employee_placement(): void
    {
        $role = $this->createRole('STAFF');
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit();

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);

        $this->createScope($user, $role, $unit->id);

        $this->assertSame(
            $unit->id,
            $this->service->getEffectiveUnitId($user)
        );
    }

    public function test_inactive_future_destination_does_not_remove_current_staff_access(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $role = $this->createRole('STAFF');
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $oldUnit->id,
            'mulai' => '2026-01-01',
            'selesai' => null,
        ]);

        $this->createScope($user, $role, $oldUnit->id);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $newUnit->id,
            '2026-12-01'
        );

        $newUnit->update(['aktif' => false]);

        $this->assertSame(
            $oldUnit->id,
            $this->service->getEffectiveUnitId($user)
        );

        $this->travelTo('2026-12-01 00:00:00');

        $this->assertNull(
            $this->service->getEffectiveUnitId($user)
        );
    }

    public function test_staff_without_active_placement_has_no_unit_access(): void
    {
        $role = $this->createRole('STAFF');
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit();

        $this->createScope($user, $role, $unit->id);

        $this->assertNull(
            $this->service->getEffectiveUnitId($user)
        );
    }

    public function test_inactive_user_has_no_active_role(): void
    {
        $role = $this->createRole('STAFF');
        $user = $this->createUser(null, false);
        $unit = $this->createUnit();

        $this->createScope($user, $role, $unit->id);

        $this->assertNull(
            $this->service->getActiveScope($user)
        );
    }

    public function test_inactive_employee_has_no_active_role(): void
    {
        $role = $this->createRole('STAFF');
        $pegawai = $this->createPegawai();

        $pegawai->update([
            'aktif' => false,
        ]);

        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit();

        $this->createScope($user, $role, $unit->id);

        $this->assertNull(
            $this->service->getActiveScope($user)
        );
    }

    public function test_staff_cannot_access_inactive_placement_unit(): void
    {
        $role = $this->createRole('STAFF');
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit();

        $unit->update([
            'aktif' => false,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);

        $this->createScope($user, $role, $unit->id);

        $this->assertNull(
            $this->service->getEffectiveUnitId($user)
        );
    }

    public function test_only_super_admin_has_global_access(): void
    {
        $superAdminRole = $this->createRole('SUPER_ADMIN');
        $staffRole = $this->createRole('STAFF');

        $superAdmin = $this->createUser();
        $staff = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($superAdmin, $superAdminRole, null);
        $this->createScope($staff, $staffRole, $unit->id);

        $this->assertTrue(
            $this->service->hasGlobalAccess($superAdmin)
        );

        $this->assertFalse(
            $this->service->hasGlobalAccess($staff)
        );
    }

    public function test_user_cannot_have_multiple_active_roles(): void
    {
        $user = $this->createUser();

        $roleStaff = $this->createRole('STAFF');
        $roleKasub = $this->createRole('KASUB');

        $unit = $this->createUnit();

        $this->createScope($user, $roleStaff, $unit->id);
        $this->createScope($user, $roleKasub, $unit->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'User memiliki lebih dari satu role aktif.'
        );

        $this->service->getActiveScope($user);
    }

    private function createUser(
        ?int $pegawaiId = null,
        bool $aktif = true
    ): User {
        return User::create([
            'pegawai_id' => $pegawaiId,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => $aktif,
        ]);
    }

    private function createPegawai(): Pegawai
    {
        return Pegawai::create([
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'email_kontak' => fake()->safeEmail(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);
    }

    private function createRole(string $kode): Role
    {
        return Role::create([
            'kode' => $kode,
            'nama' => $kode,
        ]);
    }

    private function createUnit(): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => 'SATKER',
            'aktif' => true,
        ]);
    }

    private function createScope(
        User $user,
        Role $role,
        ?int $unitId
    ): UserRoleScope {
        return UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
