<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PengajuanAkun;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PengajuanAkunApiTest extends TestCase
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

    public function test_submission_detail_includes_employee_and_complete_placement_history(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $submission = $this->createPendingSubmission($satker);
        $pegawai = $submission->user->pegawai;

        $historicalPlacement = PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $bidang->id,
            'mulai' => today()->subMonths(6),
            'selesai' => today()->subDays(2),
        ]);

        Sanctum::actingAs($actor);

        $this->getJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}"
        )
            ->assertOk()
            ->assertJsonPath('data.pegawai.id', $pegawai->id)
            ->assertJsonPath('data.pegawai.nip', $pegawai->nip)
            ->assertJsonPath('data.pegawai.nama', $pegawai->nama)
            ->assertJsonPath('data.pegawai.jenis_kepegawaian', 'PNS')
            ->assertJsonPath('data.penempatan_aktif.unit.id', $satker->id)
            ->assertJsonPath('data.penempatan_aktif.unit.kode', $satker->kode)
            ->assertJsonPath('data.penempatan_aktif.unit.nama', $satker->nama)
            ->assertJsonPath('data.jumlah_penempatan_aktif', 1)
            ->assertJsonCount(2, 'data.riwayat_penempatan')
            ->assertJsonPath(
                'data.riwayat_penempatan.0.id',
                $submission->user->pegawai->penempatan()
                    ->where('id', '!=', $historicalPlacement->id)
                    ->value('id')
            )
            ->assertJsonPath(
                'data.riwayat_penempatan.1.id',
                $historicalPlacement->id
            )
            ->assertJsonMissingPath('data.akun.firebase_uid');
    }

    public function test_submission_list_includes_employee_and_active_placement(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satker = $this->createUnit('SATKER');
        $submission = $this->createPendingSubmission($satker);
        $pegawai = $submission->user->pegawai;

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/administrasi/pengajuan-akun')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.pegawai.id', $pegawai->id)
            ->assertJsonPath('data.0.pegawai.nip', $pegawai->nip)
            ->assertJsonPath('data.0.pegawai.nama', $pegawai->nama)
            ->assertJsonPath('data.0.penempatan_aktif.unit.id', $satker->id)
            ->assertJsonPath('data.0.jumlah_penempatan_aktif', 1)
            ->assertJsonCount(1, 'data.0.riwayat_penempatan')
            ->assertJsonMissingPath('data.0.akun.firebase_uid');
    }

    public function test_unauthenticated_user_cannot_access_submissions(): void
    {
        $this->getJson('/api/v1/administrasi/pengajuan-akun')
            ->assertUnauthorized();
    }

    public function test_super_admin_can_view_pending_submissions(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $this->createPendingSubmission();

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/administrasi/pengajuan-akun')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_satker_only_sees_submissions_in_its_scope(): void
    {
        $actor = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->createScope($actor, 'ADMIN_SATKER', $satkerA->id);

        $this->createPendingSubmission($satkerA);
        $this->createPendingSubmission($satkerB);

        Sanctum::actingAs($actor);

        $this->getJson('/api/v1/administrasi/pengajuan-akun')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_satker_cannot_view_submission_outside_its_scope(): void
    {
        $actor = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->createScope($actor, 'ADMIN_SATKER', $satkerA->id);

        $submission = $this->createPendingSubmission($satkerB);

        Sanctum::actingAs($actor);

        $this->getJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}"
        )->assertForbidden();
    }

    public function test_super_admin_can_approve_submission_and_assign_role(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $submission = $this->createPendingSubmission($bidang);

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'KASATKER',
                'unit_id' => $satker->id,
                'catatan' => 'Pengajuan sesuai data pegawai.',
            ]
        )->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => true,
        ]);

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_DISETUJUI,
            'ditinjau_oleh' => $actor->id,
        ]);

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $submission->user_id,
            'role_id' => Role::where('kode', 'KASATKER')->value('id'),
            'unit_id' => $satker->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'SETUJUI_PENGAJUAN_AKUN',
            'entitas' => 'pengajuan_akun',
            'entitas_id' => $submission->id,
        ]);
    }

    public function test_admin_satker_can_approve_submission_in_its_scope(): void
    {
        $actor = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $this->createScope($actor, 'ADMIN_SATKER', $satker->id);

        $submission = $this->createPendingSubmission($bidang);

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidang->id,
            ]
        )->assertOk();

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_DISETUJUI,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => true,
        ]);
    }

    public function test_admin_satker_cannot_assign_admin_satker_role_during_approval(): void
    {
        $actor = $this->createUser();

        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'ADMIN_SATKER', $satker->id);

        $submission = $this->createPendingSubmission($satker);

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'ADMIN_SATKER',
                'unit_id' => $satker->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => false,
        ]);

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_MENUNGGU,
        ]);
    }

    public function test_approval_rejects_unit_outside_employee_placement_hierarchy(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $submission = $this->createPendingSubmission($satkerA);

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'KASATKER',
                'unit_id' => $satkerB->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => false,
        ]);
    }

    public function test_super_admin_can_approve_staff_with_placement_unit_scope(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satker = $this->createUnit('SATKER');
        $submission = $this->createPendingSubmission($satker);

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'STAFF',
                'unit_id' => $satker->id,
            ]
        )->assertOk();

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $submission->user_id,
            'role_id' => Role::where('kode', 'STAFF')->value('id'),
            'unit_id' => $satker->id,
            'selesai' => null,
        ]);
    }

    public function test_rejection_requires_reason(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $submission = $this->createPendingSubmission();

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/tolak",
            [
                'catatan' => '',
            ]
        )->assertUnprocessable();

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_MENUNGGU,
        ]);
    }

    public function test_super_admin_can_reject_submission_without_activating_account(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $submission = $this->createPendingSubmission();

        Sanctum::actingAs($actor);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/tolak",
            [
                'catatan' => 'Data pegawai tidak sesuai.',
            ]
        )->assertOk();

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_DITOLAK,
            'catatan' => 'Data pegawai tidak sesuai.',
            'ditinjau_oleh' => $actor->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => false,
        ]);

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $submission->user_id,
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'TOLAK_PENGAJUAN_AKUN',
            'entitas' => 'pengajuan_akun',
            'entitas_id' => $submission->id,
        ]);
    }

    public function test_admin_satker_only_sees_placement_history_within_its_scope(): void
    {
        $actor = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->createScope($actor, 'ADMIN_SATKER', $satkerA->id);

        $submission = $this->createPendingSubmission($satkerA);
        $pegawai = $submission->user->pegawai;

        $outsidePlacement = PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $satkerB->id,
            'mulai' => today()->subMonths(6),
            'selesai' => today()->subDays(2),
        ]);

        Sanctum::actingAs($actor);

        $response = $this->getJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}"
        )->assertOk();

        $response
            ->assertJsonPath(
                'data.penempatan_aktif.unit.id',
                $satkerA->id
            )
            ->assertJsonCount(1, 'data.riwayat_penempatan');

        $this->assertNotContains(
            $outsidePlacement->id,
            collect($response->json('data.riwayat_penempatan'))
                ->pluck('id')
                ->all()
        );
    }

    public function test_processed_submission_cannot_be_rejected_again(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $submission = $this->createPendingSubmission();

        Sanctum::actingAs($actor);

        $url = "/api/v1/administrasi/pengajuan-akun/{$submission->id}/tolak";

        $this->postJson($url, [
            'catatan' => 'Data tidak sesuai.',
        ])->assertOk();

        $this->postJson($url, [
            'catatan' => 'Percobaan penolakan kedua.',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_DITOLAK,
            'catatan' => 'Data tidak sesuai.',
        ]);
    }

    public function test_approval_is_rolled_back_when_final_audit_fails(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $satker = $this->createUnit('SATKER');
        $submission = $this->createPendingSubmission($satker);

        Sanctum::actingAs($actor);

        $auditService = Mockery::mock(AuditService::class);

        // Audit pemberian role berhasil.
        $auditService
            ->shouldReceive('record')
            ->once()
            ->withArgs(
                fn ($aksi) => $aksi === 'ASSIGN_ROLE'
            )
            ->ordered()
            ->andReturn(new LogAudit);

        // Audit persetujuan gagal.
        $auditService
            ->shouldReceive('record')
            ->once()
            ->withArgs(
                fn ($aksi) => $aksi === 'SETUJUI_PENGAJUAN_AKUN'
            )
            ->ordered()
            ->andThrow(
                new RuntimeException('Audit persetujuan gagal.')
            );

        $this->app->instance(AuditService::class, $auditService);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/setujui",
            [
                'role_code' => 'KASATKER',
                'unit_id' => $satker->id,
            ]
        )->assertServerError();

        // Aktivasi akun harus dibatalkan.
        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => false,
        ]);

        // Pengajuan harus tetap menunggu.
        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_MENUNGGU,
            'ditinjau_oleh' => null,
            'ditinjau_pada' => null,
        ]);

        // Role yang sempat dibuat harus ikut dibatalkan.
        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $submission->user_id,
        ]);

        // Tidak boleh ada audit assignment yang tertinggal.
        $this->assertDatabaseMissing('log_audit', [
            'entitas' => 'user_role_scope',
        ]);

        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'SETUJUI_PENGAJUAN_AKUN',
            'entitas_id' => $submission->id,
        ]);
    }

    public function test_rejection_is_rolled_back_when_audit_fails(): void
    {
        $actor = $this->createUser();
        $this->createScope($actor, 'SUPER_ADMIN', null);

        $submission = $this->createPendingSubmission();

        Sanctum::actingAs($actor);

        $auditService = Mockery::mock(AuditService::class);

        $auditService
            ->shouldReceive('record')
            ->once()
            ->withArgs(
                fn ($aksi) => $aksi === 'TOLAK_PENGAJUAN_AKUN'
            )
            ->andThrow(
                new RuntimeException('Audit penolakan gagal.')
            );

        $this->app->instance(AuditService::class, $auditService);

        $this->postJson(
            "/api/v1/administrasi/pengajuan-akun/{$submission->id}/tolak",
            [
                'catatan' => 'Data pegawai tidak sesuai.',
            ]
        )->assertServerError();

        // Status pengajuan harus tetap menunggu.
        $this->assertDatabaseHas('pengajuan_akun', [
            'id' => $submission->id,
            'status' => PengajuanAkun::STATUS_MENUNGGU,
            'ditinjau_oleh' => null,
            'ditinjau_pada' => null,
        ]);

        // Akun tetap tidak aktif.
        $this->assertDatabaseHas('users', [
            'id' => $submission->user_id,
            'aktif' => false,
        ]);

        // Tidak boleh ada role yang diberikan.
        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $submission->user_id,
        ]);

        // Audit penolakan tidak boleh tersimpan.
        $this->assertDatabaseMissing('log_audit', [
            'aksi' => 'TOLAK_PENGAJUAN_AKUN',
            'entitas_id' => $submission->id,
        ]);
    }

    private function createPendingSubmission(
        ?UnitOrganisasi $unit = null
    ): PengajuanAkun {
        $pegawai = $this->createPegawai();

        $user = $this->createUser($pegawai->id, false);

        $unit ??= $this->createUnit('SATKER');

        $this->createPlacement($pegawai, $unit);

        return PengajuanAkun::create([
            'user_id' => $user->id,
            'status' => PengajuanAkun::STATUS_MENUNGGU,
            'diajukan_pada' => now(),
        ]);
    }

    private function createUser(
        ?int $pegawaiId = null,
        bool $aktif = true
    ): User {
        return User::create([
            'pegawai_id' => $pegawaiId,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => $aktif,
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
        ?int $parentId = null
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => $parentId,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => "Unit {$jenis}",
            'jenis' => $jenis,
            'aktif' => true,
        ]);
    }

    private function createScope(
        User $user,
        string $roleCode,
        ?int $unitId
    ): UserRoleScope {
        return UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => Role::where('kode', $roleCode)->value('id'),
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }

    private function createPlacement(
        Pegawai $pegawai,
        UnitOrganisasi $unit
    ): PenempatanPegawai {
        return PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);
    }
}
