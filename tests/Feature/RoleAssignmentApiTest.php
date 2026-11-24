<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\PenempatanPegawaiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAssignmentApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_roles(): void
    {
        $this->getJson('/api/v1/roles')
            ->assertUnauthorized();
    }

    public function test_staff_cannot_view_role_options(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SATKER');

        $this->createScope($user, 'STAFF', $unit->id);

        $pegawai = $this->createPegawai();
        $user->update(['pegawai_id' => $pegawai->id]);

        $this->createPlacement($pegawai, $unit);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/roles')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_all_role_options(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/roles');

        $response
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    public function test_admin_satker_cannot_choose_super_admin_or_admin_satker(): void
    {
        $user = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($user, 'ADMIN_SATKER', $satker->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/roles');

        $response
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonMissing([
                'kode' => 'SUPER_ADMIN',
            ])
            ->assertJsonMissing([
                'kode' => 'ADMIN_SATKER',
            ]);
    }

    public function test_super_admin_can_assign_role_to_another_user(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'SUPER_ADMIN', null);

        Sanctum::actingAs($actor);

        $response = $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KASATKER',
                'unit_id' => $satker->id,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.role.kode', 'KASATKER')
            ->assertJsonPath('data.unit.id', $satker->id);

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KASATKER')->value('id'),
            'unit_id' => $satker->id,
            'selesai' => null,
        ]);
    }

    public function test_admin_satker_can_assign_role_inside_its_scope(): void
    {
        $actor = $this->createUser();
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $this->createScope($actor, 'ADMIN_SATKER', $satker->id);
        $this->createPlacement($pegawai, $bidang);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidang->id,
            ]
        )
            ->assertOk()
            ->assertJsonPath('data.role.kode', 'KABID');
    }

    public function test_admin_satker_cannot_assign_role_outside_its_scope(): void
    {
        $actor = $this->createUser();
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $bidangB = $this->createUnit('BIDANG', $satkerB->id);

        $this->createScope($actor, 'ADMIN_SATKER', $satkerA->id);
        $this->createPlacement($pegawai, $bidangB);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidangB->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidangB->id,
        ]);
    }

    public function test_admin_satker_a_cannot_manage_staff_after_transfer_to_satker_b(): void
    {
        $adminA = $this->createUser();
        $satkerA = $this->createUnit('SATKER');
        $bidangA = $this->createUnit('BIDANG', $satkerA->id);
        $satkerB = $this->createUnit('SATKER');
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $this->createScope($adminA, 'ADMIN_SATKER', $satkerA->id);
        $this->createScope($target, 'STAFF', $satkerA->id);
        $this->createPlacement($pegawai, $satkerA);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $satkerB->id,
            today()->toDateString()
        );

        $this->assertDatabaseHas('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $satkerB->id,
            'selesai' => null,
        ]);

        Sanctum::actingAs($adminA);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidangA->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidangA->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'STAFF')->value('id'),
            'unit_id' => $satkerB->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseHas('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'unit_id' => $satkerB->id,
        ]);
    }

    public function test_admin_satker_b_can_manage_staff_after_transfer_to_satker_b(): void
    {
        $adminB = $this->createUser();
        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');
        $bidangB = $this->createUnit('BIDANG', $satkerB->id);
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $this->createScope($adminB, 'ADMIN_SATKER', $satkerB->id);
        $this->createScope($target, 'STAFF', $satkerA->id);
        $this->createPlacement($pegawai, $satkerA);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $satkerB->id,
            today()->toDateString()
        );

        Sanctum::actingAs($adminB);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidangB->id,
            ]
        )->assertOk();

        $this->assertDatabaseHas('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidangB->id,
            'selesai' => null,
        ]);
    }

    public function test_role_history_and_management_scope_follow_the_scheduled_transfer_date(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $adminA = $this->createUser();
        $adminB = $this->createUser();
        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $this->createScope($adminA, 'ADMIN_SATKER', $satkerA->id);
        $this->createScope($adminB, 'ADMIN_SATKER', $satkerB->id);
        $this->createScope($target, 'STAFF', $satkerA->id);
        $this->createPlacement($pegawai, $satkerA);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $satkerB->id,
            '2026-12-01'
        );

        Sanctum::actingAs($adminA);

        $this->getJson("/api/v1/administrasi/pengguna/{$target->id}/role")
            ->assertOk()
            ->assertJsonPath('data.0.unit.id', $satkerB->id)
            ->assertJsonPath('data.0.aktif', false)
            ->assertJsonPath('data.1.unit.id', $satkerA->id)
            ->assertJsonPath('data.1.aktif', true);

        Sanctum::actingAs($adminB);

        $this->getJson("/api/v1/administrasi/pengguna/{$target->id}/role")
            ->assertForbidden();

        $this->travelTo('2026-12-01 00:00:00');

        Sanctum::actingAs($adminA);

        $this->getJson("/api/v1/administrasi/pengguna/{$target->id}/role")
            ->assertForbidden();

        Sanctum::actingAs($adminB);

        $this->getJson("/api/v1/administrasi/pengguna/{$target->id}/role")
            ->assertOk()
            ->assertJsonPath('data.0.unit.id', $satkerB->id)
            ->assertJsonPath('data.0.aktif', true)
            ->assertJsonPath('data.1.unit.id', $satkerA->id)
            ->assertJsonPath('data.1.aktif', false);
    }

    public function test_admin_satker_cannot_manage_staff_without_active_placement(): void
    {
        $admin = $this->createUser();
        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $this->createScope($admin, 'ADMIN_SATKER', $satker->id);
        $this->createScope($target, 'STAFF', $satker->id);

        Sanctum::actingAs($admin);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidang->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidang->id,
            'selesai' => null,
        ]);
    }

    public function test_staff_cannot_assign_role(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'STAFF', $satker->id);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KASUB',
                'unit_id' => $satker->id,
            ]
        )->assertForbidden();
    }

    public function test_admin_satker_cannot_use_fake_unit_id_outside_scope(): void
    {
        $actor = $this->createUser();
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);
        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');
        $bidangB = $this->createUnit('BIDANG', $satkerB->id);

        $this->createScope($actor, 'ADMIN_SATKER', $satkerA->id);
        $this->createPlacement($pegawai, $satkerA);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidangB->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('user_role_scope', [
            'user_id' => $target->id,
            'role_id' => Role::where('kode', 'KABID')->value('id'),
            'unit_id' => $bidangB->id,
            'selesai' => null,
        ]);
    }

    public function test_super_admin_keeps_global_assignment_access_after_transfer(): void
    {
        $superAdmin = $this->createUser();
        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');
        $bidangB = $this->createUnit('BIDANG', $satkerB->id);
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai->id);

        $this->createScope($superAdmin, 'SUPER_ADMIN', null);
        $this->createScope($target, 'STAFF', $satkerA->id);
        $this->createPlacement($pegawai, $satkerA);

        app(PenempatanPegawaiService::class)->transfer(
            $pegawai,
            $satkerB->id,
            today()->toDateString()
        );

        Sanctum::actingAs($superAdmin);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $bidangB->id,
            ]
        )->assertOk();
    }

    public function test_admin_satker_cannot_assign_admin_satker_role(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'ADMIN_SATKER', $satker->id);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'ADMIN_SATKER',
                'unit_id' => $satker->id,
            ]
        )->assertForbidden();
    }

    public function test_user_cannot_change_own_role(): void
    {
        $user = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($user, 'SUPER_ADMIN', null);

        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$user->id}/role",
            [
                'role_code' => 'KASATKER',
                'unit_id' => $satker->id,
            ]
        )->assertForbidden();
    }

    public function test_kasatker_cannot_assign_role(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'KASATKER', $satker->id);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KABID',
                'unit_id' => $this->createUnit('BIDANG', $satker->id)->id,
            ]
        )->assertForbidden();
    }

    public function test_role_assignment_requires_valid_role_and_unit(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'SUPER_ADMIN', null);

        Sanctum::actingAs($actor);

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'ROLE_TIDAK_ADA',
                'unit_id' => $satker->id,
            ]
        )->assertUnprocessable();

        $this->putJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role",
            [
                'role_code' => 'KASATKER',
                'unit_id' => null,
            ]
        )->assertUnprocessable();
    }

    public function test_role_history_can_be_viewed_by_super_admin(): void
    {
        $actor = $this->createUser();
        $target = $this->createUser();
        $satker = $this->createUnit('SATKER');

        $this->createScope($actor, 'SUPER_ADMIN', null);
        $this->createScope($target, 'KASATKER', $satker->id);

        Sanctum::actingAs($actor);

        $this->getJson(
            "/api/v1/administrasi/pengguna/{$target->id}/role"
        )
            ->assertOk()
            ->assertJsonPath('data.0.role.kode', 'KASATKER');
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
