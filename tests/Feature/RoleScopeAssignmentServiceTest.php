<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\AuditService;
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

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'STAFF wajib memiliki tepat satu penempatan aktif.'
        );

        $this->service->assignRole(
            $user,
            'STAFF'
        );
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
