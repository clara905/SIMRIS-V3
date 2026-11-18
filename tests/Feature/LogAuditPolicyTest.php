<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class LogAuditPolicyTest extends TestCase
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

    public function test_super_admin_can_view_audit_list_and_detail(): void
    {
        $admin = $this->createUser();
        $audit = $this->createAudit();

        $this->createScope($admin, 'SUPER_ADMIN');

        $this->assertTrue(
            Gate::forUser($admin)->allows('viewAny', LogAudit::class)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $audit)
        );
    }

    public function test_non_super_admin_cannot_view_audit_list_or_detail(): void
    {
        $audit = $this->createAudit();

        foreach ([
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUser();

            $this->createScope($user, $roleCode);

            $this->assertFalse(
                Gate::forUser($user)->allows('viewAny', LogAudit::class),
                "{$roleCode} tidak boleh melihat daftar audit."
            );

            $this->assertFalse(
                Gate::forUser($user)->allows('view', $audit),
                "{$roleCode} tidak boleh melihat detail audit."
            );
        }
    }

    public function test_user_without_active_role_cannot_view_audit(): void
    {
        $user = $this->createUser();
        $audit = $this->createAudit();

        $this->assertFalse(
            Gate::forUser($user)->allows('viewAny', LogAudit::class)
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $audit)
        );
    }

    public function test_inactive_super_admin_cannot_view_audit(): void
    {
        $user = $this->createUser();
        $audit = $this->createAudit();

        $this->createScope($user, 'SUPER_ADMIN');

        $user->update(['aktif' => false]);

        $this->assertFalse(
            Gate::forUser($user)->allows('viewAny', LogAudit::class)
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $audit)
        );
    }

    public function test_user_with_multiple_active_roles_cannot_view_audit(): void
    {
        $user = $this->createUser();
        $audit = $this->createAudit();

        $this->createScope($user, 'SUPER_ADMIN');
        $this->createScope($user, 'ADMIN_SATKER');

        $this->assertFalse(
            Gate::forUser($user)->allows('viewAny', LogAudit::class)
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $audit)
        );
    }

    public function test_only_super_admin_can_correct_original_audit(): void
    {
        $admin = $this->createUser();
        $original = $this->createAudit();

        $this->createScope($admin, 'SUPER_ADMIN');

        $this->assertTrue(
            Gate::forUser($admin)->allows('correct', $original)
        );

        $correction = LogAudit::create([
            'user_id' => null,
            'aksi' => 'KOREKSI_AUDIT',
            'entitas' => 'users',
            'entitas_id' => null,
            'data_lama' => null,
            'data_baru' => null,
            'ip_address' => null,
            'koreksi_dari_id' => $original->id,
            'catatan' => 'Koreksi pertama.',
            'waktu' => now(),
        ]);

        $this->assertFalse(
            Gate::forUser($admin)->allows('correct', $correction)
        );
    }

    public function test_non_super_admin_cannot_correct_audit(): void
    {
        $audit = $this->createAudit();

        foreach ([
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUser();

            $this->createScope($user, $roleCode);

            $this->assertFalse(
                Gate::forUser($user)->allows('correct', $audit),
                "{$roleCode} tidak boleh mengoreksi log audit."
            );
        }
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

    private function createAudit(): LogAudit
    {
        return LogAudit::create([
            'user_id' => null,
            'aksi' => 'UPDATE',
            'entitas' => 'users',
            'entitas_id' => null,
            'data_lama' => null,
            'data_baru' => null,
            'ip_address' => '127.0.0.1',
            'koreksi_dari_id' => null,
            'catatan' => null,
            'waktu' => now(),
        ]);
    }

    private function createScope(
        User $user,
        string $roleCode
    ): UserRoleScope {
        $role = Role::where('kode', $roleCode)->firstOrFail();

        return UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => null,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
