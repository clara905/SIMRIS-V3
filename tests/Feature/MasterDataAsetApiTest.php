<?php

namespace Tests\Feature;

use App\Models\KategoriAset;
use App\Models\Role;
use App\Models\SumberAset;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataAsetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'SUPER_ADMIN',
            'KASUB',
            'KABID',
            'KASATKER',
            'STAFF',
        ] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_asset_master(): void
    {
        $this->getJson('/api/v1/sumber-aset')
            ->assertUnauthorized();

        $this->getJson('/api/v1/kategori-aset')
            ->assertUnauthorized();

        $this->getJson('/api/v1/klasifikasi-informasi')
            ->assertUnauthorized();
    }

    public function test_kasub_can_create_and_update_sumber_aset(): void
    {
        $user = $this->createUserWithRole('KASUB');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/sumber-aset', [
            'kode' => 'APBN',
            'nama' => 'Anggaran Pendapatan dan Belanja Negara',
        ])
            ->assertCreated()
            ->assertJsonPath('data.kode', 'APBN');

        $sumber = SumberAset::where('kode', 'APBN')->firstOrFail();

        $this->putJson("/api/v1/sumber-aset/{$sumber->id}", [
            'kode' => 'APBN-01',
            'nama' => 'Anggaran Negara',
        ])
            ->assertOk()
            ->assertJsonPath('data.kode', 'APBN-01');
    }

    public function test_kasub_can_create_category_with_parent(): void
    {
        $user = $this->createUserWithRole('KASUB');

        Sanctum::actingAs($user);

        $parent = KategoriAset::create([
            'parent_id' => null,
            'kode' => 'ELEKTRONIK',
            'nama' => 'Peralatan Elektronik',
        ]);

        $this->postJson('/api/v1/kategori-aset', [
            'parent_id' => $parent->id,
            'kode' => 'KOMPUTER',
            'nama' => 'Komputer',
        ])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $parent->id);
    }

    public function test_kasub_cannot_create_category_cycle(): void
    {
        $user = $this->createUserWithRole('KASUB');

        $parent = KategoriAset::create([
            'parent_id' => null,
            'kode' => 'INDUK',
            'nama' => 'Kategori Induk',
        ]);

        $child = KategoriAset::create([
            'parent_id' => $parent->id,
            'kode' => 'ANAK',
            'nama' => 'Kategori Anak',
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/kategori-aset/{$parent->id}", [
            'parent_id' => $child->id,
            'kode' => 'INDUK',
            'nama' => 'Kategori Induk',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');
    }

    public function test_kasub_can_create_klasifikasi_informasi(): void
    {
        $user = $this->createUserWithRole('KASUB');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/klasifikasi-informasi', [
            'kode' => 'TERBATAS',
            'nama' => 'Terbatas',
            'deskripsi' => 'Informasi dengan akses terbatas.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.kode', 'TERBATAS');
    }

    public function test_kabid_can_read_but_cannot_create_master_data(): void
    {
        $user = $this->createUserWithRole('KABID');

        SumberAset::create([
            'kode' => 'APBN',
            'nama' => 'APBN',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/sumber-aset')
            ->assertOk()
            ->assertJsonFragment(['kode' => 'APBN']);

        $this->postJson('/api/v1/sumber-aset', [
            'kode' => 'HIBAH',
            'nama' => 'Hibah',
        ])->assertForbidden();
    }

    public function test_kasatker_can_read_but_cannot_create_master_data(): void
    {
        $user = $this->createUserWithRole('KASATKER');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/kategori-aset')
            ->assertOk();

        $this->postJson('/api/v1/kategori-aset', [
            'parent_id' => null,
            'kode' => 'KENDARAAN',
            'nama' => 'Kendaraan',
        ])->assertForbidden();
    }

    public function test_super_admin_can_read_master_data_but_cannot_modify_it(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/klasifikasi-informasi')
            ->assertOk();

        $this->postJson('/api/v1/klasifikasi-informasi', [
            'kode' => 'RAHASIA',
            'nama' => 'Rahasia',
            'deskripsi' => 'Informasi rahasia.',
        ])->assertForbidden();
    }

    public function test_staff_cannot_access_asset_master(): void
    {
        $user = $this->createUserWithRole('STAFF');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/sumber-aset')
            ->assertForbidden();
    }

    public function test_duplicate_master_code_is_rejected(): void
    {
        $user = $this->createUserWithRole('KASUB');

        SumberAset::create([
            'kode' => 'APBN',
            'nama' => 'APBN',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/sumber-aset', [
            'kode' => 'APBN',
            'nama' => 'Anggaran Negara',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kode');
    }

    public function test_master_delete_is_available_to_kasub(): void
    {
        $user = $this->createUserWithRole('KASUB');

        $sumber = SumberAset::create([
            'kode' => 'HIBAH',
            'nama' => 'Hibah',
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/sumber-aset/{$sumber->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('sumber_aset', [
            'id' => $sumber->id,
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $user = User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        $unitId = null;

        if ($roleCode !== 'SUPER_ADMIN') {
            $unit = UnitOrganisasi::create([
                'parent_id' => null,
                'kode' => fake()->unique()->bothify('UNIT-####'),
                'nama' => 'Unit Pengujian',
                'jenis' => match ($roleCode) {
                    'KASUB' => 'SUBBIDANG',
                    'KABID' => 'BIDANG',
                    'KASATKER' => 'SATKER',
                    default => 'SUBBAGIAN',
                },
                'aktif' => true,
            ]);

            $unitId = $unit->id;
        }

        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }
}
