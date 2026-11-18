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

class PegawaiPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SUPER_ADMIN', 'KASUB'] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_user_with_accessible_unit_can_view_pegawai_list(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'viewAny',
                Pegawai::class
            )
        );
    }

    public function test_user_can_view_pegawai_in_accessible_unit(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();
        $pegawai = $this->createPegawai();

        $this->createScope($user, 'KASUB', $unit->id);
        $this->createPlacement($pegawai, $unit);

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    public function test_user_cannot_view_pegawai_outside_accessible_unit(): void
    {
        $user = $this->createUser();

        $unitA = $this->createUnit();
        $unitB = $this->createUnit();

        $pegawai = $this->createPegawai();

        $this->createScope($user, 'KASUB', $unitA->id);
        $this->createPlacement($pegawai, $unitB);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    public function test_user_cannot_view_pegawai_with_expired_placement(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();
        $pegawai = $this->createPegawai();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->createPlacement(
            $pegawai,
            $unit,
            now()->subMonths(2)->toDateString(),
            now()->subDay()->toDateString()
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    public function test_user_cannot_view_pegawai_without_placement(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit();
        $pegawai = $this->createPegawai();

        $this->createScope($user, 'KASUB', $unit->id);

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    public function test_super_admin_can_view_pegawai_without_placement(): void
    {
        $user = $this->createUser();
        $pegawai = $this->createPegawai();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'viewAny',
                Pegawai::class
            )
        );

        $this->assertTrue(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    public function test_user_without_active_role_cannot_view_pegawai(): void
    {
        $user = $this->createUser();
        $pegawai = $this->createPegawai();

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'viewAny',
                Pegawai::class
            )
        );

        $this->assertFalse(
            Gate::forUser($user)->allows('view', $pegawai)
        );
    }

    private function createUser(): User
    {
        return User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);
    }

    private function createUnit(): UnitOrganisasi
    {
        return UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => fake()->unique()->bothify('UNIT-####'),
            'nama' => 'Unit Pengujian',
            'jenis' => 'SUBBIDANG',
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
