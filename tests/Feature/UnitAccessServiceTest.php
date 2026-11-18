<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\UnitAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnitAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UnitAccessService::class);

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

    public function test_kasatker_can_access_all_active_descendants(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);
        $subbagian = $this->createUnit('SUBBAGIAN', $subbidang->id);

        $this->createScope(
            $user,
            'KASATKER',
            $satker->id
        );

        $result = $this->service->getAccessibleUnitIds($user);

        $this->assertEqualsCanonicalizing(
            [
                $satker->id,
                $bidang->id,
                $subbidang->id,
                $subbagian->id,
            ],
            $result
        );
    }

    public function test_kabid_can_access_own_bidang_and_descendants(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidangA = $this->createUnit('BIDANG', $satker->id);
        $bidangB = $this->createUnit('BIDANG', $satker->id);

        $subbidangA = $this->createUnit('SUBBIDANG', $bidangA->id);
        $subbidangB = $this->createUnit('SUBBIDANG', $bidangB->id);

        $this->createScope($user, 'KABID', $bidangA->id);

        $result = $this->service->getAccessibleUnitIds($user);

        $this->assertEqualsCanonicalizing(
            [$bidangA->id, $subbidangA->id],
            $result
        );

        $this->assertNotContains($bidangB->id, $result);
        $this->assertNotContains($subbidangB->id, $result);
    }

    public function test_staff_can_only_access_placement_unit(): void
    {
        $pegawai = $this->createPegawai();
        $user = $this->createUser($pegawai->id);

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $this->createScope($user, 'STAFF', null);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $subbidang->id,
            'mulai' => today()->subDay(),
            'selesai' => null,
        ]);

        $result = $this->service->getAccessibleUnitIds($user);

        $this->assertSame([$subbidang->id], $result);
    }

    public function test_super_admin_has_global_access(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $this->assertNull(
            $this->service->getAccessibleUnitIds($user)
        );
    }

    public function test_inactive_descendant_is_not_accessible(): void
    {
        $user = $this->createUser();

        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id, false);

        $this->createScope($user, 'KASATKER', $satker->id);

        $result = $this->service->getAccessibleUnitIds($user);

        $this->assertSame([$satker->id], $result);
        $this->assertNotContains($bidang->id, $result);
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
        ?int $parentId = null,
        bool $aktif = true
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => $parentId,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => $jenis,
            'aktif' => $aktif,
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
