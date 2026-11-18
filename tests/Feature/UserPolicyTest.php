<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPolicyTest extends TestCase
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

    public function test_super_admin_can_view_all_users(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $this->createScope($admin, 'SUPER_ADMIN', null);

        $this->assertTrue(
            Gate::forUser($admin)->allows('viewAny', User::class)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_admin_satker_can_view_user_inside_satker_scope(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $this->createScope($admin, 'ADMIN_SATKER', $satker->id);
        $this->createScope($target, 'KASUB', $subbidang->id);

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_kasatker_can_view_user_inside_satker_scope(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $this->createScope($admin, 'KASATKER', $satker->id);
        $this->createScope($target, 'KABID', $bidang->id);

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_kabid_can_view_user_inside_bidang_scope(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $bidang = $this->createUnit('BIDANG');
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $this->createScope($admin, 'KABID', $bidang->id);
        $this->createScope($target, 'KASUB', $subbidang->id);

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_kasub_can_view_staff_in_its_placement_unit(): void
    {
        $admin = $this->createUser();
        $pegawai = $this->createPegawai();
        $target = $this->createUser($pegawai);

        $subbidang = $this->createUnit('SUBBIDANG');

        $this->createScope($admin, 'KASUB', $subbidang->id);
        $this->createScope($target, 'STAFF', null);
        $this->createPlacement($pegawai, $subbidang);

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_user_cannot_view_target_outside_accessible_scope(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->createScope($admin, 'ADMIN_SATKER', $satkerA->id);
        $this->createScope($target, 'KASATKER', $satkerB->id);

        $this->assertFalse(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    public function test_user_without_active_role_cannot_view_user_list(): void
    {
        $user = $this->createUser();

        $this->assertFalse(
            Gate::forUser($user)->allows('viewAny', User::class)
        );
    }

    public function test_staff_cannot_view_user_list(): void
    {
        $user = $this->createUser();
        $pegawai = $this->createPegawai();
        $unit = $this->createUnit('SUBBIDANG');

        $user->update(['pegawai_id' => $pegawai->id]);

        $this->createPlacement($pegawai, $unit);
        $this->createScope($user, 'STAFF', null);

        $this->assertFalse(
            Gate::forUser($user)->allows('viewAny', User::class)
        );
    }

    public function test_admin_satker_can_view_pending_user_with_placement_in_scope(): void
    {
        $admin = $this->createUser();
        $pegawai = $this->createPegawai();
        $pendingUser = $this->createUser($pegawai);

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $this->createScope($admin, 'ADMIN_SATKER', $satker->id);
        $this->createPlacement($pegawai, $subbidang);

        // Pending user sengaja belum memiliki role aktif.
        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $pendingUser)
        );
    }

    public function test_admin_satker_cannot_view_pending_user_outside_scope(): void
    {
        $admin = $this->createUser();
        $pegawai = $this->createPegawai();
        $pendingUser = $this->createUser($pegawai);

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->createScope($admin, 'ADMIN_SATKER', $satkerA->id);
        $this->createPlacement($pegawai, $satkerB);

        $this->assertFalse(
            Gate::forUser($admin)->allows('view', $pendingUser)
        );
    }

    public function test_user_cannot_view_target_with_multiple_active_scopes(): void
    {
        $admin = $this->createUser();
        $target = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $this->createScope($admin, 'ADMIN_SATKER', $satker->id);
        $this->createScope($target, 'KASATKER', $satker->id);
        $this->createScope($target, 'KABID', $bidang->id);

        $this->assertFalse(
            Gate::forUser($admin)->allows('view', $target)
        );
    }

    private function createUser(?Pegawai $pegawai = null): User
    {
        return User::create([
            'pegawai_id' => $pegawai?->id,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
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
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
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

    private function createPlacement(
        Pegawai $pegawai,
        UnitOrganisasi $unit,
        ?string $mulai = null,
        ?string $selesai = null
    ): PenempatanPegawai {
        return PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => $mulai ?? now()->subDay()->toDateString(),
            'selesai' => $selesai,
        ]);
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
