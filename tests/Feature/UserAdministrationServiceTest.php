<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\AuditService;
use App\Services\UserAdministrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class UserAdministrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserAdministrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserAdministrationService::class);

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

    public function test_deactivate_user_and_revoke_all_tokens(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $user->createToken('device-1');
        $user->createToken('device-2');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $result = $this->service->deactivate($user);

        $this->assertFalse($result->aktif);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'aktif' => false,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'DEACTIVATE_USER',
            'entitas' => 'users',
            'entitas_id' => $user->id,
        ]);
    }

    public function test_deactivate_rejects_already_inactive_user(): void
    {
        $user = $this->createUser(false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Akun sudah dalam keadaan nonaktif.'
        );

        $this->service->deactivate($user);
    }

    public function test_deactivate_rejects_user_without_role(): void
    {
        $user = $this->createUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Penonaktifan membutuhkan tepat satu role aktif.'
        );

        $this->service->deactivate($user);
    }

    public function test_deactivate_rejects_user_with_future_role(): void
    {
        $user = $this->createUser();

        $role = Role::where('kode', 'SUPER_ADMIN')->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => null,
            'mulai' => now()->addDay(),
            'selesai' => null,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Role akun belum berlaku karena tanggal mulai masih di masa depan.'
        );

        $this->service->deactivate($user);
    }

    public function test_deactivation_is_rolled_back_when_audit_fails(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $user->createToken('device-1');

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit gagal disimpan.'));

        $this->app->instance(AuditService::class, $auditService);

        $service = app(UserAdministrationService::class);

        try {
            $service->deactivate($user);

            $this->fail(
                'Penonaktifan seharusnya gagal ketika audit gagal.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit gagal disimpan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'aktif' => true,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'DEACTIVATE_USER',
            'entitas_id' => $user->id,
        ]);
    }

    public function test_reactivate_user_with_valid_kasub_scope(): void
    {
        $user = $this->createUser(false);

        $unit = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit->id);

        $result = $this->service->reactivate($user);

        $this->assertTrue($result->aktif);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'aktif' => true,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'REACTIVATE_USER',
            'entitas' => 'users',
            'entitas_id' => $user->id,
        ]);
    }

    public function test_reactivate_super_admin_without_unit(): void
    {
        $user = $this->createUser(false);

        $this->createScope($user, 'SUPER_ADMIN', null);

        $result = $this->service->reactivate($user);

        $this->assertTrue($result->aktif);
    }

    public function test_reactivate_staff_with_valid_placement(): void
    {
        $pegawai = $this->createPegawai();

        $user = $this->createUser(false, $pegawai);

        $unit = $this->createUnit('SATKER');

        $this->createScope($user, 'STAFF', null);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => today()->subDays(10),
            'selesai' => null,
        ]);

        $result = $this->service->reactivate($user);

        $this->assertTrue($result->aktif);
    }

    public function test_reactivate_rejects_user_without_role(): void
    {
        $user = $this->createUser(false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Aktivasi kembali membutuhkan tepat satu role yang belum berakhir.'
        );

        $this->service->reactivate($user);
    }

    public function test_reactivate_rejects_multiple_open_roles(): void
    {
        $user = $this->createUser(false);

        $unit1 = $this->createUnit('SUBBIDANG');
        $unit2 = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit1->id);
        $this->createScope($user, 'KASUB', $unit2->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Aktivasi kembali membutuhkan tepat satu role yang belum berakhir.'
        );

        $this->service->reactivate($user);
    }

    public function test_reactivate_rejects_inactive_employee(): void
    {
        $pegawai = $this->createPegawai(false);

        $user = $this->createUser(false, $pegawai);

        $unit = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Pegawai tidak ditemukan atau sudah nonaktif.'
        );

        $this->service->reactivate($user);
    }

    public function test_reactivate_rejects_inactive_unit(): void
    {
        $user = $this->createUser(false);

        $unit = $this->createUnit('SUBBIDANG', false);

        $this->createScope($user, 'KASUB', $unit->id);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unit organisasi tidak ditemukan atau nonaktif.'
        );

        $this->service->reactivate($user);
    }

    public function test_reactivation_is_rolled_back_when_audit_fails(): void
    {
        $user = $this->createUser(false);

        $unit = $this->createUnit('SUBBIDANG');

        $this->createScope($user, 'KASUB', $unit->id);

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit gagal disimpan.'));

        $this->app->instance(AuditService::class, $auditService);

        $service = app(UserAdministrationService::class);

        try {
            $service->reactivate($user);

            $this->fail(
                'Aktivasi kembali seharusnya gagal ketika audit gagal.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit gagal disimpan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'aktif' => false,
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'REACTIVATE_USER',
            'entitas_id' => $user->id,
        ]);
    }

    public function test_reactivate_rejects_user_with_future_role(): void
    {
        $user = $this->createUser(false);

        $unit = $this->createUnit('SUBBIDANG');

        $role = Role::where('kode', 'KASUB')->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unit->id,
            'mulai' => now()->addDay(),
            'selesai' => null,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Role akun belum berlaku karena tanggal mulai masih di masa depan.'
        );

        $this->service->reactivate($user);
    }

    public function test_reactivate_rejects_already_active_user(): void
    {
        $user = $this->createUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Akun sudah dalam keadaan aktif.'
        );

        $this->service->reactivate($user);
    }

    private function createUser(
        bool $aktif = true,
        ?Pegawai $pegawai = null,
    ): User {
        return User::create([
            'pegawai_id' => $pegawai?->id,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => $aktif,
        ]);
    }

    private function createPegawai(bool $aktif = true): Pegawai
    {
        return Pegawai::create([
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'email_kontak' => fake()->safeEmail(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => $aktif,
        ]);
    }

    private function createUnit(
        string $jenis,
        bool $aktif = true,
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
            'aktif' => $aktif,
        ]);
    }

    private function createScope(
        User $user,
        string $roleCode,
        ?int $unitId,
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
