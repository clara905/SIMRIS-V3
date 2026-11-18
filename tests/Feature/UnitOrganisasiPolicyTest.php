<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UnitOrganisasiPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SUPER_ADMIN', 'KASUB'] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_user_with_accessible_unit_can_view_unit_list(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'viewAny',
                UnitOrganisasi::class
            )
        );
    }

    public function test_user_can_view_unit_within_scope(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $unit)
        );
    }

    public function test_user_cannot_view_unit_outside_scope(): void
    {
        $user = $this->createUser();

        $unitA = $this->createUnit();
        $unitB = $this->createUnit();

        $this->createScope($user, 'KASUB', $unitA->id);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $unitB)
        );
    }

    public function test_user_cannot_view_inactive_unit(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit(false);

        $this->createScope($user, 'KASUB', $unit->id);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $unit)
        );
    }

    public function test_super_admin_can_view_all_active_units(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $unit = $this->createUnit();

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'viewAny',
                UnitOrganisasi::class
            )
        );

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $unit)
        );
    }

    public function test_user_without_active_role_cannot_view_units(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit();

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'viewAny',
                UnitOrganisasi::class
            )
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $unit)
        );
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

    private function createUnit(bool $aktif = true): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => 'SUBBIDANG',
            'aktif' => $aktif,
        ]);
    }

    private function createScope(
        User $user,
        string $roleCode,
        ?int $unitId
    ): UserRoleScope {
        $role = Role::where('kode', $roleCode)->firstOrFail();

        return UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
