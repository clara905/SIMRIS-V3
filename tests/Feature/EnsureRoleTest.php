<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth:sanctum',
            'simris.role:KASUB',
        ])->get('/_test/role-access', function () {
            return response()->json([
                'message' => 'Berhasil.',
            ]);
        });

        foreach (['SUPER_ADMIN', 'KASUB', 'KABID'] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_user_with_allowed_role_can_access_route(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/role-access')
            ->assertOk()
            ->assertJson([
                'message' => 'Berhasil.',
            ]);
    }

    public function test_user_with_wrong_role_is_forbidden(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit('BIDANG');

        $this->createScope($user, 'KABID', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/role-access')
            ->assertForbidden();
    }

    public function test_inactive_user_is_forbidden(): void
    {
        $user = $this->createUser(false);

        $unit = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/role-access')
            ->assertForbidden();
    }

    public function test_user_without_active_role_is_forbidden(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/role-access')
            ->assertForbidden();
    }

    private function createUser(bool $aktif = true): User
    {
        return User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => $aktif,
        ]);
    }

    private function createUnit(string $jenis): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
            'aktif' => true,
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
