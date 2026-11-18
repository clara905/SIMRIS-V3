<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserAdministrationApiTest extends TestCase
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

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATION
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_deactivate_account(): void
    {
        $target = $this->createUser();

        $this->createScope($target, 'SUPER_ADMIN', null);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/nonaktifkan"
        );

        $response->assertUnauthorized();

        $this->assertTrue($target->fresh()->aktif);
    }

    public function test_unauthenticated_user_cannot_reactivate_account(): void
    {
        $target = $this->createUser([
            'aktif' => false,
        ]);

        $this->createScope($target, 'SUPER_ADMIN', null);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/aktifkan-kembali"
        );

        $response->assertUnauthorized();

        $this->assertFalse($target->fresh()->aktif);
    }

    /*
    |--------------------------------------------------------------------------
    | DEACTIVATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_deactivate_account_through_api(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        $targetToken = $target->createToken('target-device')->plainTextToken;

        $this->assertSame(1, $target->tokens()->count());

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/nonaktifkan",
            [
                'catatan' => 'Akun dinonaktifkan untuk pengujian.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Akun berhasil dinonaktifkan.'
            )
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.username', $target->username)
            ->assertJsonPath('data.aktif', false);

        $this->assertFalse($target->fresh()->aktif);

        // Semua token milik target harus dicabut.
        $this->assertSame(0, $target->tokens()->count());

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'name' => 'target-device',
        ]);

        // Audit penonaktifan harus tercatat.
        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'DEACTIVATE_USER',
            'entitas' => 'users',
            'entitas_id' => $target->id,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'entitas' => 'users',
            'entitas_id' => $target->id,
            'catatan' => 'Akun dinonaktifkan untuk pengujian.',
        ]);
    }

    public function test_staff_cannot_deactivate_account_through_api(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $this->createScope($admin, 'STAFF', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/nonaktifkan"
        );

        $response->assertForbidden();

        $this->assertTrue($target->fresh()->aktif);
    }

    public function test_user_cannot_deactivate_own_account_through_api(): void
    {
        $admin = $this->createUser();

        $this->createScope($admin, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$admin->id}/nonaktifkan"
        );

        $response->assertForbidden();

        $this->assertTrue($admin->fresh()->aktif);
    }

    /*
    |--------------------------------------------------------------------------
    | REACTIVATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_reactivate_account_through_api(): void
    {
        $admin = $this->createUser();

        $target = $this->createUser([
            'aktif' => false,
        ]);

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/aktifkan-kembali",
            [
                'catatan' => 'Akun diaktifkan kembali untuk pengujian.',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Akun berhasil diaktifkan kembali.'
            )
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.username', $target->username)
            ->assertJsonPath('data.aktif', true);

        $this->assertTrue($target->fresh()->aktif);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'REACTIVATE_USER',
            'entitas' => 'users',
            'entitas_id' => $target->id,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'entitas' => 'users',
            'entitas_id' => $target->id,
            'catatan' => 'Akun diaktifkan kembali untuk pengujian.',
        ]);
    }

    public function test_staff_cannot_reactivate_account_through_api(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser([
            'aktif' => false,
        ]);

        $this->createScope($admin, 'STAFF', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/aktifkan-kembali"
        );

        $response->assertForbidden();

        $this->assertFalse($target->fresh()->aktif);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    public function test_deactivate_rejects_catatan_longer_than_2000_characters(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/nonaktifkan",
            [
                'catatan' => str_repeat('A', 2001),
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('catatan');

        $this->assertTrue($target->fresh()->aktif);
    }

    public function test_reactivate_rejects_catatan_longer_than_2000_characters(): void
    {
        $admin = $this->createUser();

        $target = $this->createUser([
            'aktif' => false,
        ]);

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/aktifkan-kembali",
            [
                'catatan' => str_repeat('A', 2001),
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('catatan');

        $this->assertFalse($target->fresh()->aktif);
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE VALIDATION THROUGH API
    |--------------------------------------------------------------------------
    */

    public function test_api_returns_422_when_target_is_already_inactive(): void
    {
        $admin = $this->createUser();

        $target = $this->createUser([
            'aktif' => false,
        ]);

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/nonaktifkan"
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Akun sudah dalam keadaan nonaktif.'
            );

        $this->assertFalse($target->fresh()->aktif);
    }

    public function test_api_returns_422_when_target_is_already_active(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $this->createScope($admin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'SUPER_ADMIN', null);

        Sanctum::actingAs($admin);

        $response = $this->patchJson(
            "/api/v1/administrasi/pengguna/{$target->id}/aktifkan-kembali"
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Akun sudah dalam keadaan aktif.'
            );

        $this->assertTrue($target->fresh()->aktif);
    }

    /*
    |--------------------------------------------------------------------------
    | TEST HELPERS
    |--------------------------------------------------------------------------
    */

    private function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ], $attributes));
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
