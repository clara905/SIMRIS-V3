<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUnitAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth:sanctum',
            'simris.unit',
        ])->get('/_test/unit-access', function (Request $request) {
            return response()->json([
                'unit_ids' => $request->attributes->get(
                    'simris_accessible_unit_ids'
                ),
                'global' => $request->attributes->get(
                    'simris_global_unit_access'
                ),
            ]);
        });

        Route::middleware([
            'auth:sanctum',
            'simris.unit',
        ])->get('/_test/unit-access/{unit_id}', function () {
            return response()->json([
                'message' => 'Akses unit berhasil.',
            ]);
        });

        foreach (['SUPER_ADMIN', 'KASUB'] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_user_receives_accessible_unit_scope(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/unit-access')
            ->assertOk()
            ->assertJson([
                'unit_ids' => [$unit->id],
                'global' => false,
            ]);
    }

    public function test_user_can_access_unit_within_scope(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/unit-access/'.$unit->id)
            ->assertOk()
            ->assertJson([
                'message' => 'Akses unit berhasil.',
            ]);
    }

    public function test_user_cannot_access_unit_outside_scope(): void
    {
        $user = $this->createUser();

        $unitA = $this->createUnit();
        $unitB = $this->createUnit();

        $this->createScope($user, 'KASUB', $unitA->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/unit-access/'.$unitB->id)
            ->assertForbidden();
    }

    public function test_super_admin_has_global_unit_access(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $unit = $this->createUnit();

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/unit-access/'.$unit->id)
            ->assertOk();
    }

    public function test_invalid_unit_parameter_is_rejected(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/unit-access/abc')
            ->assertForbidden();
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

    private function createUnit(): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => 'SUBBIDANG',
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
