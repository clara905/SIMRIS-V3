<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\KategoriAset;
use App\Models\KlasifikasiInformasi;
use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PenugasanAset;
use App\Models\Role;
use App\Models\SumberAset;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\AuditService;
use App\Services\PenempatanPegawaiService;
use App\Services\RoleScopeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PenempatanPegawaiServiceTest extends TestCase
{
    use RefreshDatabase;

    private PenempatanPegawaiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PenempatanPegawaiService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_assign_initial_placement_to_employee(): void
    {
        $pegawai = $this->createPegawai();
        $unit = $this->createUnit();

        $placement = $this->service->assignInitial(
            $pegawai,
            $unit->id,
            '2026-01-01'
        );

        $this->assertSame($pegawai->id, $placement->pegawai_id);
        $this->assertSame($unit->id, $placement->unit_id);
        $this->assertSame('2026-01-01', $placement->mulai->toDateString());
        $this->assertNull($placement->selesai);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'ASSIGN_PLACEMENT',
            'entitas' => 'penempatan_pegawai',
            'entitas_id' => $placement->id,
        ]);
    }

    public function test_initial_placement_rejects_existing_placement_history(): void
    {
        $pegawai = $this->createPegawai();
        $unit = $this->createUnit();

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => '2026-01-01',
            'selesai' => '2026-01-31',
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Pegawai sudah memiliki riwayat penempatan.'
        );

        $this->service->assignInitial(
            $pegawai,
            $this->createUnit()->id,
            '2026-02-01'
        );
    }

    public function test_transfer_closes_old_placement_one_day_before_new_placement(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $newPlacement = $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );

        $oldPlacement->refresh();

        $this->assertSame(
            '2026-05-31',
            $oldPlacement->selesai->toDateString()
        );

        $this->assertSame(
            '2026-06-01',
            $newPlacement->mulai->toDateString()
        );

        $this->assertNull($newPlacement->selesai);

        $this->assertSame($newUnit->id, $newPlacement->unit_id);
    }

    public function test_scheduled_transfer_preserves_old_effective_unit_until_effective_date(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $user = $this->createUser($pegawai);

        $staffRole = Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'mulai' => now(),
            'selesai' => null,
        ]);

        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $newPlacement = $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-12-01'
        );

        $oldPlacement->refresh();
        $roleScopeService = app(RoleScopeService::class);
        $activeScope = $roleScopeService->getActiveScope($user);

        $this->assertSame(
            '2026-11-30',
            $oldPlacement->selesai->toDateString()
        );

        $this->assertSame(
            '2026-12-01',
            $newPlacement->mulai->toDateString()
        );
        $this->assertSame($oldUnit->id, $activeScope?->unit_id);

        $oldScope = UserRoleScope::query()
            ->where('user_id', $user->id)
            ->where('unit_id', $oldUnit->id)
            ->firstOrFail();

        $this->assertSame(
            '2026-12-01 00:00:00',
            $oldScope->selesai->toDateTimeString()
        );

        // Sebelum tanggal efektif, STAFF masih menggunakan unit lama.
        $this->assertSame(
            $oldUnit->id,
            $roleScopeService->getEffectiveUnitId($user)
        );

        $this->travelTo('2026-11-30 23:59:59');

        $this->assertSame(
            $oldUnit->id,
            $roleScopeService->getActiveScope($user)?->unit_id
        );

        // Tepat pada tanggal efektif, unit STAFF berpindah.
        $this->travelTo('2026-12-01 00:00:00');

        $this->assertSame(
            $newUnit->id,
            $roleScopeService->getEffectiveUnitId($user)
        );
        $this->assertSame(
            $newUnit->id,
            $roleScopeService->getActiveScope($user)?->unit_id
        );

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $newUnit->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CHANGE_ROLE',
            'entitas' => 'user_role_scope',
        ]);

        $scopeAudit = LogAudit::query()
            ->where('aksi', 'CHANGE_ROLE')
            ->firstOrFail();

        $this->assertSame(
            $oldUnit->id,
            $scopeAudit->data_lama['unit_id']
        );
        $this->assertSame(
            $newUnit->id,
            $scopeAudit->data_baru['unit_id']
        );
        $this->assertSame(
            '2026-12-01',
            Carbon::parse($scopeAudit->data_baru['mulai'])->toDateString()
        );
    }

    public function test_cancelling_scheduled_transfer_restores_asset_assignment(): void
    {
        $this->travelTo('2026-02-01 10:00:00');

        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );
        $aset = $this->createAssetForTransferTest($oldUnit);
        $assignment = $this->createAssetAssignmentForTransferTest(
            $aset,
            $pegawai,
            '2026-01-15'
        );

        $scheduledPlacement = $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );

        $this->assertTrue(
            PenugasanAset::query()
                ->whereKey($assignment->id)
                ->whereDate('selesai', '2026-05-31')
                ->exists()
        );

        $restoredPlacement = $this->service->cancelScheduledTransfer($pegawai);

        $this->assertSame($oldPlacement->id, $restoredPlacement->id);
        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacement->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseMissing('penempatan_pegawai', [
            'id' => $scheduledPlacement->id,
        ]);
        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'RESTORE_ASSET_ASSIGNMENT',
            'entitas_id' => $assignment->id,
        ]);
        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CANCEL_SCHEDULED_TRANSFER',
            'entitas_id' => $scheduledPlacement->id,
        ]);
    }

    public function test_cancelling_scheduled_transfer_rolls_back_when_audit_fails(): void
    {
        $this->travelTo('2026-02-01 10:00:00');

        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $user = $this->createUser($pegawai);
        $staffRole = Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);
        $scope = UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'mulai' => '2026-01-01 00:00:00',
            'selesai' => null,
        ]);
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );
        $aset = $this->createAssetForTransferTest($oldUnit);
        $assignment = $this->createAssetAssignmentForTransferTest(
            $aset,
            $pegawai,
            '2026-01-15'
        );
        $scheduledPlacement = $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );
        $newScopeId = UserRoleScope::query()
            ->where('user_id', $user->id)
            ->where('unit_id', $newUnit->id)
            ->value('id');

        $auditService = Mockery::mock(AuditService::class);
        $auditService
            ->shouldReceive('record')
            ->times(3)
            ->andReturnUsing(function (...$arguments): LogAudit {
                if ($arguments[0] === 'CANCEL_SCHEDULED_TRANSFER') {
                    throw new RuntimeException('Audit pembatalan gagal.');
                }

                return new LogAudit;
            });

        $this->app->instance(AuditService::class, $auditService);
        $service = app(PenempatanPegawaiService::class);

        try {
            $service->cancelScheduledTransfer($pegawai);

            $this->fail(
                'Pembatalan seharusnya gagal ketika audit tidak dapat disimpan.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit pembatalan gagal.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            PenempatanPegawai::query()
                ->whereKey($oldPlacement->id)
                ->whereDate('selesai', '2026-05-31')
                ->exists()
        );
        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $scheduledPlacement->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseHas('user_role_scope', [
            'id' => $scope->id,
            'selesai' => '2026-06-01 00:00:00',
        ]);
        $this->assertDatabaseHas('user_role_scope', [
            'id' => $newScopeId,
            'unit_id' => $newUnit->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => '2026-05-31 00:00:00',
        ]);
        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'CANCEL_ROLE_SCOPE_TRANSFER',
            'entitas_id' => $newScopeId,
        ]);
        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'RESTORE_ASSET_ASSIGNMENT',
            'entitas_id' => $assignment->id,
        ]);
        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'CANCEL_SCHEDULED_TRANSFER',
            'entitas_id' => $scheduledPlacement->id,
        ]);
    }

    public function test_cancelling_transfer_is_rejected_when_destination_assets_are_assigned(): void
    {
        $this->travelTo('2026-02-01 10:00:00');

        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );
        $scheduledPlacement = $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );
        $destinationAsset = $this->createAssetForTransferTest($newUnit);
        $assignment = $this->createAssetAssignmentForTransferTest(
            $destinationAsset,
            $pegawai,
            '2026-06-01'
        );

        try {
            $this->service->cancelScheduledTransfer($pegawai);

            $this->fail(
                'Pembatalan harus ditolak ketika unit tujuan sudah memiliki penugasan aset mendatang.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Transfer tidak dapat dibatalkan karena sudah ada penugasan aset pada unit tujuan setelah tanggal efektif.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            PenempatanPegawai::query()
                ->whereKey($oldPlacement->id)
                ->whereDate('selesai', '2026-05-31')
                ->exists()
        );
        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $scheduledPlacement->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => null,
        ]);
        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'CANCEL_SCHEDULED_TRANSFER',
            'entitas_id' => $scheduledPlacement->id,
        ]);
    }

    public function test_transfer_does_not_synchronize_a_future_staff_scope(): void
    {
        Carbon::setTestNow('2026-11-18 10:00:00');

        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $user = $this->createUser($pegawai);
        $staffRole = Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);
        $futureScope = UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'mulai' => now()->addDay(),
            'selesai' => null,
        ]);

        $this->createPlacement($pegawai, $oldUnit, '2026-01-01');

        $this->service->transfer(
            $pegawai,
            $newUnit->id,
            today()->toDateString()
        );

        $this->assertDatabaseHas('user_role_scope', [
            'id' => $futureScope->id,
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'CHANGE_ROLE',
            'entitas' => 'user_role_scope',
        ]);
    }

    public function test_transfer_does_not_synchronize_scope_for_an_inactive_user(): void
    {
        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $user = $this->createUser($pegawai);
        $staffRole = Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);
        $scope = UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
        $this->createPlacement($pegawai, $oldUnit, '2026-01-01');
        $user->update(['aktif' => false]);

        $this->service->transfer(
            $pegawai,
            $newUnit->id,
            today()->toDateString()
        );

        $this->assertDatabaseHas('user_role_scope', [
            'id' => $scope->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'CHANGE_ROLE',
            'entitas' => 'user_role_scope',
        ]);
    }

    public function test_transfer_rolls_back_when_staff_scope_audit_fails(): void
    {
        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $user = $this->createUser($pegawai);
        $staffRole = Role::create([
            'kode' => 'STAFF',
            'nama' => 'Staff',
        ]);
        $scope = UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $auditService = Mockery::mock(AuditService::class);
        $auditService
            ->shouldReceive('record')
            ->once()
            ->andReturnUsing(function (...$arguments): LogAudit {
                if ($arguments[0] === 'CHANGE_ROLE') {
                    throw new RuntimeException('Scope audit gagal disimpan.');
                }

                return new LogAudit;
            });

        $this->app->instance(AuditService::class, $auditService);
        $service = app(PenempatanPegawaiService::class);

        try {
            $service->transfer(
                $pegawai,
                $newUnit->id,
                today()->toDateString()
            );

            $this->fail('Transfer seharusnya gagal saat audit scope gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Scope audit gagal disimpan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacement->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $newUnit->id,
        ]);

        $this->assertDatabaseHas('user_role_scope', [
            'id' => $scope->id,
            'user_id' => $user->id,
            'role_id' => $staffRole->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);

        $this->assertSame(0, LogAudit::query()->count());
    }

    public function test_transfer_automatically_ends_old_unit_asset_assignments(): void
    {
        $pegawai = $this->createPegawai();
        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $this->createPlacement($pegawai, $oldUnit, '2026-01-01');

        $aset = $this->createAssetForTransferTest($oldUnit);

        $assignment = $this->createAssetAssignmentForTransferTest(
            $aset,
            $pegawai,
            '2026-01-15'
        );

        $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );

        $assignment->refresh();

        $this->assertSame(
            '2026-05-31',
            $assignment->selesai->toDateString()
        );

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'END_ASSET_ASSIGNMENT',
            'entitas' => 'penugasan_aset',
            'entitas_id' => $assignment->id,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CHANGE_PLACEMENT',
            'entitas' => 'penempatan_pegawai',
        ]);
    }

    public function test_transfer_does_not_end_assignment_for_another_asset_owner_unit(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();
        $otherUnit = $this->createUnit();

        $this->createPlacement($pegawai, $oldUnit, '2026-01-01');

        $otherAsset = $this->createAssetForTransferTest($otherUnit);

        $otherAssignment = $this->createAssetAssignmentForTransferTest(
            $otherAsset,
            $pegawai,
            '2026-01-15'
        );

        $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-06-01'
        );

        $otherAssignment->refresh();

        $this->assertNull($otherAssignment->selesai);
    }

    public function test_transfer_rejects_future_asset_assignment(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $aset = $this->createAssetForTransferTest($oldUnit);

        $assignment = $this->createAssetAssignmentForTransferTest(
            $aset,
            $pegawai,
            '2026-06-01'
        );

        try {
            $this->service->transfer(
                $pegawai,
                $newUnit->id,
                '2026-06-01'
            );

            $this->fail(
                'Transfer seharusnya ditolak karena ada penugasan aset masa depan.'
            );
        } catch (LogicException $exception) {
            $this->assertStringContainsString(
                'penugasan aset yang baru dimulai',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacement->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $newUnit->id,
        ]);

        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'END_ASSET_ASSIGNMENT',
            'entitas_id' => $assignment->id,
        ]);
    }

    public function test_transfer_rolls_back_asset_assignment_when_assignment_audit_fails(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $aset = $this->createAssetForTransferTest($oldUnit);

        $assignment = $this->createAssetAssignmentForTransferTest(
            $aset,
            $pegawai,
            '2026-01-15'
        );

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit penugasan gagal.'));

        $this->app->instance(AuditService::class, $auditService);

        $service = app(PenempatanPegawaiService::class);

        try {
            $service->transfer(
                $pegawai,
                $newUnit->id,
                '2026-06-01'
            );

            $this->fail(
                'Transfer seharusnya gagal ketika audit penugasan aset gagal.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit penugasan gagal.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacement->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $newUnit->id,
        ]);

        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'END_ASSET_ASSIGNMENT',
            'entitas_id' => $assignment->id,
        ]);
    }

    public function test_transfer_rejects_same_destination_unit(): void
    {
        $pegawai = $this->createPegawai();
        $unit = $this->createUnit();

        $this->createPlacement(
            $pegawai,
            $unit,
            '2026-01-01'
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unit tujuan sama dengan unit penempatan sebelumnya.'
        );

        $this->service->transfer(
            $pegawai,
            $unit->id,
            '2026-06-01'
        );
    }

    public function test_transfer_rejects_inactive_destination_unit(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $inactiveUnit = $this->createUnit(false);

        $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unit organisasi tidak aktif.'
        );

        $this->service->transfer(
            $pegawai,
            $inactiveUnit->id,
            '2026-06-01'
        );
    }

    public function test_transfer_rejects_overlapping_placement_history(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $otherUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        // Riwayat lain yang sudah ada dan bertumpang tindih
        // dengan tanggal mulai penempatan baru.
        $this->createPlacement(
            $pegawai,
            $otherUnit,
            '2026-06-01',
            '2026-12-31'
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Tanggal penempatan baru bertumpang tindih dengan riwayat penempatan lain.'
        );

        $this->service->transfer(
            $pegawai,
            $newUnit->id,
            '2026-07-01'
        );
    }

    public function test_close_placement_preserves_history_and_creates_audit(): void
    {
        $pegawai = $this->createPegawai();
        $unit = $this->createUnit();

        $placement = $this->createPlacement(
            $pegawai,
            $unit,
            '2026-01-01'
        );

        $closedPlacement = $this->service->close(
            $pegawai,
            '2026-06-30'
        );

        $this->assertSame($placement->id, $closedPlacement->id);

        $this->assertSame(
            '2026-06-30',
            $closedPlacement->selesai->toDateString()
        );

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $placement->id,
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CLOSE_PLACEMENT',
            'entitas' => 'penempatan_pegawai',
            'entitas_id' => $placement->id,
        ]);
    }

    public function test_transfer_is_rolled_back_when_audit_fails(): void
    {
        $pegawai = $this->createPegawai();

        $oldUnit = $this->createUnit();
        $newUnit = $this->createUnit();

        $oldPlacement = $this->createPlacement(
            $pegawai,
            $oldUnit,
            '2026-01-01'
        );

        $oldPlacementId = $oldPlacement->id;

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->andThrow(new RuntimeException('Audit gagal disimpan.'));

        $this->app->instance(AuditService::class, $auditService);

        $service = app(PenempatanPegawaiService::class);

        try {
            $service->transfer(
                $pegawai,
                $newUnit->id,
                '2026-06-01'
            );

            $this->fail(
                'Transfer seharusnya gagal ketika audit gagal.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Audit gagal disimpan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('penempatan_pegawai', [
            'id' => $oldPlacementId,
            'pegawai_id' => $pegawai->id,
            'unit_id' => $oldUnit->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseMissing('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $newUnit->id,
        ]);

        $this->assertSame(
            0,
            LogAudit::query()
                ->where('aksi', 'CHANGE_PLACEMENT')
                ->count()
        );
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

    private function createUser(Pegawai $pegawai): User
    {
        return User::create([
            'pegawai_id' => $pegawai->id,
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
            'jenis' => 'SATKER',
            'aktif' => $aktif,
        ]);
    }

    private function createPlacement(
        Pegawai $pegawai,
        UnitOrganisasi $unit,
        string $mulai,
        ?string $selesai = null
    ): PenempatanPegawai {
        return PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => $mulai,
            'selesai' => $selesai,
        ]);
    }

    private function createAssetForTransferTest(
        UnitOrganisasi $unit
    ): Aset {
        $sumber = SumberAset::create([
            'kode' => fake()->unique()->bothify('SRC-####'),
            'nama' => 'Sumber Pengujian',
        ]);

        $kategori = KategoriAset::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('KAT-####'),
            'nama' => 'Kategori Pengujian',
        ]);
        $klasifikasi = KlasifikasiInformasi::create([
            'kode' => fake()->unique()->bothify('KLAS-####'),
            'nama' => 'Klasifikasi Pengujian',
            'deskripsi' => 'Klasifikasi untuk pengujian.',
        ]);

        return Aset::query()->create([
            'sumber_id' => $sumber->id,
            'unit_pemilik_id' => $unit->id,
            'kategori_id' => $kategori->id,
            'klasifikasi_informasi_id' => $klasifikasi->id,
            'kode_aset' => fake()->unique()->bothify('AST-####'),
            'nama' => 'Aset Pengujian',
            'deskripsi' => 'Aset untuk pengujian mutasi.',
            'merk' => 'Lenovo',
            'nomor_seri' => fake()->unique()->bothify('SN-######'),
            'jumlah' => 1,
            'kondisi' => 'BAIK',
            'lokasi' => 'Ruang Pengujian',
            'tanggal_pengadaan' => '2026-01-15',
            'status' => 'AKTIF',
        ]);
    }

    private function createAssetAssignmentForTransferTest(
        Aset $aset,
        Pegawai $pegawai,
        string $mulai
    ): PenugasanAset {
        return PenugasanAset::query()->create([
            'aset_idx' => $aset->idx,
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => $mulai,
            'selesai' => null,
        ]);
    }
}
