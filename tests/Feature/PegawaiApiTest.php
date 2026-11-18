<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PegawaiApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASUB',
        ] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_pegawai_api(): void
    {
        $this->getJson('/api/v1/pegawai')
            ->assertUnauthorized();

        $this->postJson('/api/v1/pegawai', [])
            ->assertUnauthorized();
    }

    public function test_super_admin_can_create_pegawai(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $unit = $this->createUnit('SATKER');

        $this->actingAs($user)
            ->postJson('/api/v1/pegawai', [
                'nip' => '199001012020011001',
                'nama' => 'Pegawai Pengujian',
                'email_kontak' => 'pegawai@example.com',
                'jenis_kepegawaian' => 'PNS',
                'unit_id' => $unit->id,
                'tanggal_mulai' => today()->toDateString(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.nip', '199001012020011001')
            ->assertJsonPath('data.nama', 'Pegawai Pengujian');

        $this->assertDatabaseHas('pegawai', [
            'nip' => '199001012020011001',
            'aktif' => true,
        ]);

        $pegawai = Pegawai::where('nip', '199001012020011001')->firstOrFail();

        $this->assertTrue(
            PenempatanPegawai::query()
                ->where('pegawai_id', $pegawai->id)
                ->where('unit_id', $unit->id)
                ->whereDate('mulai', today())
                ->whereNull('selesai')
                ->exists()
        );

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'CREATE_PEGAWAI',
            'entitas' => 'pegawai',
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'ASSIGN_PLACEMENT',
            'entitas' => 'penempatan_pegawai',
        ]);
    }

    public function test_admin_satker_can_create_pegawai_in_own_scope(): void
    {
        $user = $this->createUser();
        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);

        $this->assignRole($user, 'ADMIN_SATKER', $satker->id);

        $this->actingAs($user)
            ->postJson('/api/v1/pegawai', [
                'nip' => '199001012020011002',
                'nama' => 'Pegawai Satker',
                'jenis_kepegawaian' => 'PPPK',
                'unit_id' => $bidang->id,
                'tanggal_mulai' => today()->toDateString(),
            ])
            ->assertCreated();
    }

    public function test_admin_satker_cannot_create_pegawai_outside_scope(): void
    {
        $user = $this->createUser();

        $satkerA = $this->createUnit('SATKER');
        $satkerB = $this->createUnit('SATKER');

        $this->assignRole($user, 'ADMIN_SATKER', $satkerA->id);

        $this->actingAs($user)
            ->postJson('/api/v1/pegawai', [
                'nip' => '199001012020011003',
                'nama' => 'Pegawai Luar Satker',
                'jenis_kepegawaian' => 'PNS',
                'unit_id' => $satkerB->id,
                'tanggal_mulai' => today()->toDateString(),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('pegawai', [
            'nip' => '199001012020011003',
        ]);
    }

    public function test_kasub_cannot_create_pegawai(): void
    {
        $user = $this->createUser();
        $unit = $this->createUnit('SUBBIDANG');

        $this->assignRole($user, 'KASUB', $unit->id);

        $this->actingAs($user)
            ->postJson('/api/v1/pegawai', [
                'nip' => '199001012020011004',
                'nama' => 'Pegawai Kasub',
                'jenis_kepegawaian' => 'PNS',
                'unit_id' => $unit->id,
                'tanggal_mulai' => today()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_duplicate_nip_is_rejected(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $unit = $this->createUnit('SATKER');

        Pegawai::create([
            'nip' => '199001012020011005',
            'nama' => 'Pegawai Lama',
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/pegawai', [
                'nip' => '199001012020011005',
                'nama' => 'Pegawai Baru',
                'jenis_kepegawaian' => 'PNS',
                'unit_id' => $unit->id,
                'tanggal_mulai' => today()->toDateString(),
            ])
            ->assertUnprocessable();
    }

    public function test_super_admin_can_view_pegawai_list(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        Pegawai::create([
            'nip' => '199001012020011006',
            'nama' => 'Pegawai Daftar',
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/pegawai')
            ->assertOk()
            ->assertJsonFragment([
                'nip' => '199001012020011006',
            ]);
    }

    public function test_super_admin_can_update_pegawai(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $pegawai = $this->createPegawai();

        $this->actingAs($user)
            ->putJson("/api/v1/pegawai/{$pegawai->id}", [
                'nip' => $pegawai->nip,
                'nama' => 'Nama Diperbarui',
                'email_kontak' => 'baru@example.com',
                'jenis_kepegawaian' => 'PPPK',
            ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Nama Diperbarui');

        $this->assertDatabaseHas('pegawai', [
            'id' => $pegawai->id,
            'nama' => 'Nama Diperbarui',
            'jenis_kepegawaian' => 'PPPK',
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'UPDATE_PEGAWAI',
            'entitas_id' => $pegawai->id,
        ]);
    }

    public function test_super_admin_can_deactivate_pegawai(): void
    {
        $user = $this->createUser();
        $this->assignRole($user, 'SUPER_ADMIN');

        $unit = $this->createUnit('SATKER');
        $pegawai = $this->createPegawai();

        $pegawai->penempatan()->create([
            'unit_id' => $unit->id,
            'mulai' => today()->subMonth()->toDateString(),
            'selesai' => null,
        ]);

        $this->actingAs($user)
            ->patchJson(
                "/api/v1/pegawai/{$pegawai->id}/nonaktifkan",
                [
                    'tanggal_selesai' => today()->toDateString(),
                    'catatan' => 'Data dummy dinonaktifkan.',
                ]
            )
            ->assertOk();

        $this->assertDatabaseHas('pegawai', [
            'id' => $pegawai->id,
            'aktif' => false,
        ]);

        $this->assertDatabaseHas('penempatan_pegawai', [
            'pegawai_id' => $pegawai->id,
            'selesai' => today()->startOfDay()->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'DEACTIVATE_PEGAWAI',
            'entitas_id' => $pegawai->id,
        ]);
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
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);
    }

    private function assignRole(
        User $user,
        string $roleCode,
        ?int $unitId = null
    ): void {
        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
