<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\AuditService;
use App\Services\PenempatanPegawaiService;
use App\Services\RoleScopeAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RoleScopeAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleScopeAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RoleScopeAssignmentService::class);

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

    public function test_assign_role_to_user(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER');

        $scope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );

        $this->assertSame('ADMIN_SATKER', $scope->role->kode);
        $this->assertSame($unit->id, $scope->unit_id);
        $this->assertNull($scope->selesai);
    }

    public function test_role_change_preserves_previous_history(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG');

        $oldScope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $satker->id
        );

        $newScope = $this->service->assignRole(
            $user,
            'KABID',
            $bidang->id
        );

        $oldScope->refresh();

        $this->assertNotNull($oldScope->selesai);
        $this->assertSame('KABID', $newScope->role->kode);
        $this->assertNull($newScope->selesai);
    }

    public function test_super_admin_must_not_have_unit_id(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'SUPER_ADMIN tidak boleh memiliki unit_id.'
        );

        $this->service->assignRole(
            $user,
            'SUPER_ADMIN',
            $unit->id
        );
    }

    public function test_first_role_assignment_creates_audit_record(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER');

        $scope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );

        $audit = LogAudit::query()
            ->where('aksi', 'ASSIGN_ROLE')
            ->firstOrFail();

        $this->assertSame('user_role_scope', $audit->entitas);
        $this->assertSame($scope->id, $audit->entitas_id);
        $this->assertNull($audit->data_lama);

        $this->assertSame(
            'ADMIN_SATKER',
            $audit->data_baru['role_kode']
        );

        $this->assertSame(
            $unit->id,
            $audit->data_baru['unit_id']
        );
    }

    public function test_role_change_records_old_and_new_scope(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG');

        $oldScope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $satker->id
        );

        $newScope = $this->service->assignRole(
            $user,
            'KABID',
            $bidang->id
        );

        $audit = LogAudit::query()
            ->where('aksi', 'CHANGE_ROLE')
            ->firstOrFail();

        $this->assertSame(
            $oldScope->id,
            $audit->data_lama['scope_id']
        );

        $this->assertSame(
            'ADMIN_SATKER',
            $audit->data_lama['role_kode']
        );

        $this->assertSame(
            $newScope->id,
            $audit->data_baru['scope_id']
        );

        $this->assertSame(
            'KABID',
            $audit->data_baru['role_kode']
        );
    }

    public function test_same_role_assignment_does_not_create_duplicate_audit(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER');

        $firstScope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );

        $secondScope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );

        $this->assertSame($firstScope->id, $secondScope->id);

        $this->assertSame(
            1,
            LogAudit::query()
                ->where('aksi', 'ASSIGN_ROLE')
                ->count()
        );

        $this->assertSame(
            0,
            LogAudit::query()
                ->where('aksi', 'CHANGE_ROLE')
                ->count()
        );
    }

    public function test_role_rejects_wrong_unit_type(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('BIDANG');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Jenis unit organisasi tidak sesuai dengan role.'
        );

        $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );
    }

    public function test_staff_requires_active_placement(): void
    {
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit('SATKER');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'STAFF wajib memiliki tepat satu penempatan aktif.'
        );

        $this->service->assignRole(
            $user,
            'STAFF',
            $unit->id
        );
    }

    public function test_staff_role_scope_must_match_active_placement(): void
    {
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $placementUnit = $this->createUnit('SATKER');
        $otherUnit = $this->createUnit('BIDANG');

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $placementUnit->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unit scope STAFF harus sesuai dengan penempatan aktifnya.'
        );

        $this->service->assignRole($user, 'STAFF', $otherUnit->id);
    }

    public function test_staff_receives_the_active_placement_as_its_unit_scope(): void
    {
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $unit = $this->createUnit('SATKER');

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);

        $scope = $this->service->assignRole($user, 'STAFF', $unit->id);

        $this->assertSame($unit->id, $scope->unit_id);
    }

    public function test_new_role_assignment_is_rejected_while_transfer_is_pending(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $oldUnit = $this->createUnit('SATKER');
        $newUnit = $this->createUnit('SATKER');

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $oldUnit->id,
            'mulai' => '2026-01-01',
            'selesai' => null,
        ]);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $newUnit->id,
            '2026-12-01'
        );

        try {
            $this->service->assignRole($user, 'KASATKER', $oldUnit->id);

            $this->fail('Assignment role harus ditolak selama transfer pending.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'Role tidak dapat diubah selama transfer pegawai masih terjadwal.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('user_role_scope', 0);
        $this->assertTrue(
            PenempatanPegawai::query()
                ->where('pegawai_id', $pegawai->id)
                ->where('unit_id', $newUnit->id)
                ->whereDate('mulai', '2026-12-01')
                ->whereNull('selesai')
                ->exists()
        );
    }

    public function test_role_and_unit_scope_changes_are_rejected_while_transfer_is_pending(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);
        $oldUnit = $this->createUnit('SATKER');
        $newUnit = $this->createUnit('SATKER');

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $oldUnit->id,
            'mulai' => '2026-01-01',
            'selesai' => null,
        ]);

        $oldScope = $this->service->assignRole(
            $user,
            'STAFF',
            $oldUnit->id
        );

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $newUnit->id,
            '2026-12-01'
        );

        foreach ([
            ['KASATKER', $oldUnit->id],
            ['STAFF', $newUnit->id],
        ] as [$roleCode, $unitId]) {
            try {
                $this->service->assignRole($user, $roleCode, $unitId);

                $this->fail('Perubahan role/scope harus ditolak saat transfer pending.');
            } catch (LogicException $exception) {
                $this->assertSame(
                    'Role tidak dapat diubah selama transfer pegawai masih terjadwal.',
                    $exception->getMessage()
                );
            }
        }

        $this->assertDatabaseHas('user_role_scope', [
            'id' => $oldScope->id,
            'user_id' => $user->id,
            'role_id' => Role::where('kode', 'STAFF')->value('id'),
            'unit_id' => $oldUnit->id,
            'selesai' => '2026-12-01 00:00:00',
        ]);
    }

    public function test_inactive_unit_cannot_receive_role_assignment(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER', false);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unit organisasi tidak aktif.'
        );

        $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $unit->id
        );
    }

    public function test_role_assignment_is_rolled_back_when_audit_fails(): void
    {
        $user = $this->createUser();
        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG');

        $oldScope = $this->service->assignRole(
            $user,
            'ADMIN_SATKER',
            $satker->id
        );

        $oldScopeId = $oldScope->id;

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit gagal disimpan.'));

        $this->app->instance(AuditService::class, $auditService);

        $service = app(RoleScopeAssignmentService::class);

        try {
            $service->assignRole(
                $user,
                'KABID',
                $bidang->id
            );

            $this->fail('Seharusnya assignment gagal ketika audit gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit gagal disimpan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('user_role_scope', [
            'id' => $oldScopeId,
            'user_id' => $user->id,
            'role_id' => Role::where('kode', 'ADMIN_SATKER')->value('id'),
            'unit_id' => $satker->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $user->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidang->id,
        ]);

        $this->assertSame(
            1,
            LogAudit::query()
                ->where('aksi', 'ASSIGN_ROLE')
                ->count()
        );

        $this->assertSame(
            0,
            LogAudit::query()
                ->where('aksi', 'CHANGE_ROLE')
                ->count()
        );
    }

    private function createUser(?int $pegawaiId = null): User
    {
        return User::create([
            'pegawai_id' => $pegawaiId,
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
            'email_kontak' => fake()->safeEmail(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);
    }

    private function createUnit(
        string $jenis,
        bool $aktif = true
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
            'aktif' => $aktif,
        ]);
    }
}
