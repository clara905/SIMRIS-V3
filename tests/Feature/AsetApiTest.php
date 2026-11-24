<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\KategoriAset;
use App\Models\KlasifikasiInformasi;
use App\Models\LogAudit;
use App\Models\Role;
use App\Models\SumberAset;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class AsetApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_asset_api(): void
    {
        $this->getJson('/api/v1/aset')
            ->assertUnauthorized();

        $this->postJson('/api/v1/aset', [])
            ->assertUnauthorized();
    }

    public function test_asset_create_update_and_delete_are_audited_with_snapshots(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);

        Sanctum::actingAs($user);

        // CREATE
        $this->postJson('/api/v1/aset', $this->assetPayload($unit))
            ->assertCreated();

        $aset = Aset::query()->firstOrFail();

        $createAudit = LogAudit::query()
            ->where('aksi', 'CREATE_ASET')
            ->firstOrFail();

        $this->assertSame('aset', $createAudit->entitas);
        $this->assertSame($aset->idx, $createAudit->entitas_id);
        $this->assertNull($createAudit->data_lama);
        $this->assertSame(
            'Laptop Operasional',
            $createAudit->data_baru['nama']
        );

        // UPDATE
        $this->putJson("/api/v1/aset/{$aset->idx}", [
            'nama' => 'Laptop Operasional Updated',
        ])->assertOk();

        $updateAudit = LogAudit::query()
            ->where('aksi', 'UPDATE_ASET')
            ->firstOrFail();

        $this->assertSame(
            'Laptop Operasional',
            $updateAudit->data_lama['nama']
        );

        $this->assertSame(
            'Laptop Operasional Updated',
            $updateAudit->data_baru['nama']
        );

        // DELETE
        $this->deleteJson("/api/v1/aset/{$aset->idx}")
            ->assertOk();

        $deleteAudit = LogAudit::query()
            ->where('aksi', 'DELETE_ASET')
            ->firstOrFail();

        $this->assertSame($aset->idx, $deleteAudit->entitas_id);
        $this->assertSame(
            'Laptop Operasional Updated',
            $deleteAudit->data_lama['nama']
        );
        $this->assertNull($deleteAudit->data_baru);
    }

    public function test_asset_creation_is_rolled_back_when_audit_fails(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);

        Sanctum::actingAs($user);

        $this->mock(AuditService::class, function ($mock) {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Audit gagal.'));
        });

        $this->postJson('/api/v1/aset', $this->assetPayload($unit))
            ->assertInternalServerError();

        $this->assertDatabaseCount('aset', 0);
        $this->assertDatabaseCount('log_audit', 0);
    }

    public function test_asset_update_is_rolled_back_when_audit_fails(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);

        Sanctum::actingAs($user);

        $this->mock(AuditService::class, function ($mock) {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Audit gagal.'));
        });

        $this->putJson("/api/v1/aset/{$aset->idx}", [
            'nama' => 'Nama yang Seharusnya Tidak Tersimpan',
        ])->assertInternalServerError();

        $this->assertDatabaseHas('aset', [
            'idx' => $aset->idx,
            'nama' => 'Laptop Operasional',
        ]);

        $this->assertDatabaseCount('log_audit', 0);
    }

    public function test_kasub_can_create_view_update_and_delete_own_asset(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);

        Sanctum::actingAs($user);

        $payload = $this->assetPayload($unit);
        $response = $this->postJson('/api/v1/aset', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.nama', 'Laptop Operasional');

        $aset = Aset::query()->firstOrFail();

        $this->getJson("/api/v1/aset/{$aset->idx}")
            ->assertOk()
            ->assertJsonPath('data.kode_aset', $aset->kode_aset);

        $this->putJson("/api/v1/aset/{$aset->idx}", [
            'nama' => 'Laptop Operasional Updated',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Laptop Operasional Updated');

        $this->deleteJson("/api/v1/aset/{$aset->idx}")
            ->assertOk();

        $this->assertDatabaseMissing('aset', [
            'idx' => $aset->idx,
        ]);
    }

    public function test_kasub_cannot_create_asset_for_another_subbidang(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/aset', $this->assetPayload($otherUnit))
            ->assertForbidden();
    }

    public function test_kasub_cannot_view_asset_owned_by_another_subbidang(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);
        $aset = $this->createAsset($otherUnit);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aset/{$aset->idx}")
            ->assertForbidden();
    }

    public function test_kasub_cannot_change_asset_owner_through_regular_update(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);
        $aset = $this->createAsset($ownUnit);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/aset/{$aset->idx}", [
            'nama' => 'Nama Baru',
            'unit_pemilik_id' => $otherUnit->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit_pemilik_id');

        $this->assertDatabaseHas('aset', [
            'idx' => $aset->idx,
            'unit_pemilik_id' => $ownUnit->id,
        ]);
    }

    public function test_kabid_can_only_view_assets_in_its_subbidang_descendants(): void
    {
        $bidang = $this->createUnit('BIDANG');
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $otherBidang = $this->createUnit('BIDANG');
        $otherSubbidang = $this->createUnit('SUBBIDANG', $otherBidang->id);

        $user = $this->createUserWithRole('KABID', $bidang);

        $visibleAsset = $this->createAsset($subbidang);
        $hiddenAsset = $this->createAsset($otherSubbidang);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/aset')
            ->assertOk()
            ->assertJsonFragment([
                'kode_aset' => $visibleAsset->kode_aset,
            ])
            ->assertJsonMissing([
                'kode_aset' => $hiddenAsset->kode_aset,
            ]);

        $this->getJson("/api/v1/aset/{$visibleAsset->idx}")
            ->assertOk();

        $this->getJson("/api/v1/aset/{$hiddenAsset->idx}")
            ->assertForbidden();
    }

    public function test_kasatker_can_only_view_assets_in_its_satker_descendants(): void
    {
        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $otherSatker = $this->createUnit('SATKER');
        $otherBidang = $this->createUnit('BIDANG', $otherSatker->id);
        $otherSubbidang = $this->createUnit('SUBBIDANG', $otherBidang->id);

        $user = $this->createUserWithRole('KASATKER', $satker);

        $visibleAsset = $this->createAsset($subbidang);
        $hiddenAsset = $this->createAsset($otherSubbidang);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/aset')
            ->assertOk()
            ->assertJsonFragment([
                'kode_aset' => $visibleAsset->kode_aset,
            ])
            ->assertJsonMissing([
                'kode_aset' => $hiddenAsset->kode_aset,
            ]);

        $this->getJson("/api/v1/aset/{$hiddenAsset->idx}")
            ->assertForbidden();
    }

    public function test_kabid_and_kasatker_cannot_create_or_update_assets(): void
    {
        $bidang = $this->createUnit('BIDANG');
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $user = $this->createUserWithRole('KABID', $bidang);
        $aset = $this->createAsset($subbidang);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/aset', $this->assetPayload($subbidang))
            ->assertForbidden();

        $this->putJson("/api/v1/aset/{$aset->idx}", [
            'nama' => 'Perubahan oleh KABID',
        ])->assertForbidden();

        $satker = $this->createUnit('SATKER');
        $kasatker = $this->createUserWithRole('KASATKER', $satker);

        Sanctum::actingAs($kasatker);

        $this->postJson('/api/v1/aset', $this->assetPayload($subbidang))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_all_assets_but_cannot_modify_them(): void
    {
        $unitA = $this->createUnit('SUBBIDANG');
        $unitB = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('SUPER_ADMIN');
        $assetA = $this->createAsset($unitA);
        $assetB = $this->createAsset($unitB);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/aset')
            ->assertOk()
            ->assertJsonFragment([
                'kode_aset' => $assetA->kode_aset,
            ])
            ->assertJsonFragment([
                'kode_aset' => $assetB->kode_aset,
            ]);

        $this->putJson("/api/v1/aset/{$assetA->idx}", [
            'nama' => 'Perubahan Super Admin',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/aset/{$assetA->idx}")
            ->assertForbidden();
    }

    public function test_staff_cannot_access_asset_api(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('STAFF', $unit);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/aset')
            ->assertForbidden();
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

    private function createUserWithRole(
        string $roleCode,
        ?UnitOrganisasi $unit = null
    ): User {
        $user = User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unit?->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function assetPayload(UnitOrganisasi $unit): array
    {
        $sumber = SumberAset::firstOrCreate(
            ['kode' => 'APBN'],
            ['nama' => 'Anggaran Pendapatan dan Belanja Negara']
        );

        $kategori = KategoriAset::firstOrCreate(
            ['kode' => 'ELEKTRONIK'],
            ['parent_id' => null, 'nama' => 'Peralatan Elektronik']
        );
        $klasifikasi = KlasifikasiInformasi::firstOrCreate(
            ['kode' => 'RAHASIA'],
            ['nama' => 'Rahasia', 'deskripsi' => 'Informasi rahasia']
        );

        return [
            'sumber_id' => $sumber->id,
            'unit_pemilik_id' => $unit->id,
            'kategori_id' => $kategori->id,
            'klasifikasi_informasi_id' => $klasifikasi->id,
            'kode_aset' => fake()->unique()->bothify('AST-####'),
            'nama' => 'Laptop Operasional',
            'deskripsi' => 'Laptop untuk kegiatan operasional.',
            'merk' => 'Lenovo',
            'nomor_seri' => 'SN-'.fake()->unique()->numerify('######'),
            'jumlah' => 1,
            'kondisi' => 'BAIK',
            'lokasi' => 'Ruang Kerja',
            'tanggal_pengadaan' => '2026-01-15',
            'status' => 'AKTIF',
        ];
    }

    private function createAsset(UnitOrganisasi $unit): Aset
    {
        return Aset::query()->create($this->assetPayload($unit));
    }
}
