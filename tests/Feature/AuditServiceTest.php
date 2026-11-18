<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'kode' => 'SUPER_ADMIN',
            'nama' => 'Super Admin',
        ]);

        Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);
    }

    public function test_record_uses_authenticated_actor_snapshot(): void
    {
        $user = $this->createUser('admin.penguji');

        $this->actingAs($user);

        $audit = app(AuditService::class)->record(
            aksi: 'UPDATE',
            entitas: 'users',
            entitasId: 10,
            dataLama: ['aktif' => false],
            dataBaru: ['aktif' => true],
            catatan: 'Pengujian pencatatan audit.',
        );

        $this->assertSame($user->id, $audit->user_id);
        $this->assertSame('admin.penguji', $audit->pelaku_nama);
        $this->assertSame('admin.penguji', $audit->pelaku_username);
        $this->assertSame('UPDATE', $audit->aksi);
        $this->assertSame('users', $audit->entitas);
        $this->assertSame(10, $audit->entitas_id);
        $this->assertSame(
            ['aktif' => false],
            $audit->data_lama
        );
        $this->assertSame(
            ['aktif' => true],
            $audit->data_baru
        );
        $this->assertNotNull($audit->waktu);
    }

    public function test_record_supports_system_actor(): void
    {
        $audit = app(AuditService::class)->record(
            aksi: 'SYSTEM_CHECK',
            entitas: 'users',
        );

        $this->assertNull($audit->user_id);
        $this->assertNull($audit->pelaku_nama);
        $this->assertNull($audit->pelaku_username);
    }

    public function test_super_admin_correction_creates_new_audit_record(): void
    {
        $admin = $this->createUser('super.admin');
        $this->assignRole($admin, 'SUPER_ADMIN');

        $this->actingAs($admin);

        $original = LogAudit::create([
            'user_id' => null,
            'aksi' => 'UPDATE',
            'entitas' => 'users',
            'entitas_id' => 10,
            'data_lama' => ['aktif' => false],
            'data_baru' => ['aktif' => true],
            'ip_address' => null,
            'koreksi_dari_id' => null,
            'catatan' => null,
            'waktu' => now(),
        ]);

        $correction = app(AuditService::class)->correct(
            original: $original,
            catatan: 'Koreksi atas kesalahan pencatatan.',
            dataBaru: ['aktif' => false],
        );

        $original->refresh();

        $this->assertNotSame($original->id, $correction->id);
        $this->assertSame(
            $original->id,
            $correction->koreksi_dari_id
        );
        $this->assertSame('KOREKSI_AUDIT', $correction->aksi);
        $this->assertSame('users', $correction->entitas);
        $this->assertSame(10, $correction->entitas_id);
        $this->assertSame(
            'Koreksi atas kesalahan pencatatan.',
            $correction->catatan
        );
        $this->assertSame(
            ['aktif' => false],
            $correction->data_baru
        );

        $this->assertNull($original->koreksi_dari_id);
        $this->assertSame('UPDATE', $original->aksi);
        $this->assertSame(
            ['aktif' => true],
            $original->data_baru
        );
    }

    public function test_correction_requires_non_empty_note(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Catatan koreksi wajib diisi.'
        );

        $original = LogAudit::create([
            'user_id' => null,
            'aksi' => 'UPDATE',
            'entitas' => 'users',
            'entitas_id' => null,
            'data_lama' => null,
            'data_baru' => null,
            'ip_address' => null,
            'koreksi_dari_id' => null,
            'catatan' => null,
            'waktu' => now(),
        ]);

        app(AuditService::class)->correct(
            original: $original,
            catatan: '   ',
        );
    }

    private function createUser(string $username): User
    {
        return User::create([
            'pegawai_id' => null,
            'username' => $username,
            'email' => $username.'@example.com',
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);
    }

    private function assignRole(
        User $user,
        string $roleCode,
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
