<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\RoleScopeAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
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

        $this->assertSame(
            'ADMIN_SATKER',
            $scope->role->kode
        );

        $this->assertSame(
            $unit->id,
            $scope->unit_id
        );

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
