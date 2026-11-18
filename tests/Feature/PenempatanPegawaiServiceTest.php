<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
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
        Carbon::setTestNow('2026-11-18 10:00:00');

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
            'unit_id' => null,
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

        $this->assertSame(
            '2026-11-30',
            $oldPlacement->selesai->toDateString()
        );

        $this->assertSame(
            '2026-12-01',
            $newPlacement->mulai->toDateString()
        );

        $roleScopeService = app(RoleScopeService::class);

        // Sebelum tanggal efektif, STAFF masih menggunakan unit lama.
        $this->assertSame(
            $oldUnit->id,
            $roleScopeService->getEffectiveUnitId($user)
        );

        // Tepat pada tanggal efektif, unit STAFF berpindah.
        Carbon::setTestNow('2026-12-01 00:00:00');

        $this->assertSame(
            $newUnit->id,
            $roleScopeService->getEffectiveUnitId($user)
        );
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
}
