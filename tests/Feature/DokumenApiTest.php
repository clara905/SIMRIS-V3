<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DokumenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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

    public function test_unauthenticated_user_cannot_access_document_api(): void
    {
        $this->getJson('/api/v1/dokumen')
            ->assertUnauthorized();

        $this->postJson('/api/v1/dokumen', [])
            ->assertUnauthorized();
    }

    public function test_kasub_can_upload_and_download_private_document(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/dokumen', [
            'unit_pemilik_id' => $unit->id,
            'judul' => 'Dokumentasi Perangkat',
            'jenis' => 'FOTO_KONDISI',
            'file' => UploadedFile::fake()->create(
                'kondisi.pdf',
                100,
                'application/pdf'
            ),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.judul', 'Dokumentasi Perangkat')
            ->assertJsonPath('data.jenis', 'FOTO_KONDISI')
            ->assertJsonPath(
                'data.status_pemeriksaan',
                'MENUNGGU_PEMERIKSAAN'
            );

        $dokumen = Dokumen::query()->firstOrFail();

        $this->assertSame($unit->id, $dokumen->unit_pemilik_id);
        $this->assertSame($user->id, $dokumen->dibuat_oleh);
        $this->assertSame(64, strlen($dokumen->checksum_sha256));

        Storage::disk('local')->assertExists(
            $dokumen->storage_key
        );

        $this->assertDatabaseHas('log_audit', [
            'aksi' => 'UPLOAD_DOKUMEN',
            'entitas' => 'dokumen',
            'entitas_id' => $dokumen->id,
        ]);

        $this->getJson("/api/v1/dokumen/{$dokumen->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $dokumen->id);

        $this->get("/api/v1/dokumen/{$dokumen->id}/download")
            ->assertOk();
    }

    public function test_kasub_cannot_upload_document_for_another_subbidang(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/dokumen', [
            'unit_pemilik_id' => $otherUnit->id,
            'judul' => 'Dokumen Unit Lain',
            'jenis' => 'FOTO_KONDISI',
            'file' => UploadedFile::fake()->create(
                'dokumen.pdf',
                100,
                'application/pdf'
            ),
        ])->assertForbidden();

        $this->assertDatabaseCount('dokumen', 0);
    }

    public function test_kasub_cannot_view_or_download_document_from_another_subbidang(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);
        $dokumen = $this->createDocument($otherUnit);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/dokumen/{$dokumen->id}")
            ->assertForbidden();

        $this->get("/api/v1/dokumen/{$dokumen->id}/download")
            ->assertForbidden();
    }

    public function test_kabid_can_read_documents_in_its_descendants_but_cannot_upload(): void
    {
        $bidang = $this->createUnit('BIDANG');
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $otherBidang = $this->createUnit('BIDANG');
        $otherSubbidang = $this->createUnit(
            'SUBBIDANG',
            $otherBidang->id
        );

        $user = $this->createUserWithRole('KABID', $bidang);
        $visible = $this->createDocument($subbidang);
        $hidden = $this->createDocument($otherSubbidang);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dokumen')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id);

        $this->getJson("/api/v1/dokumen/{$visible->id}")
            ->assertOk();

        $this->getJson("/api/v1/dokumen/{$hidden->id}")
            ->assertForbidden();

        $this->postJson('/api/v1/dokumen', [
            'unit_pemilik_id' => $subbidang->id,
            'judul' => 'Upload oleh Kabid',
            'jenis' => 'FOTO_KONDISI',
            'file' => UploadedFile::fake()->create(
                'dokumen.pdf',
                100,
                'application/pdf'
            ),
        ])->assertForbidden();
    }

    public function test_kasatker_can_read_documents_in_its_descendants(): void
    {
        $satker = $this->createUnit('SATKER');
        $bidang = $this->createUnit('BIDANG', $satker->id);
        $subbidang = $this->createUnit('SUBBIDANG', $bidang->id);

        $otherSatker = $this->createUnit('SATKER');
        $otherBidang = $this->createUnit(
            'BIDANG',
            $otherSatker->id
        );
        $otherSubbidang = $this->createUnit(
            'SUBBIDANG',
            $otherBidang->id
        );

        $user = $this->createUserWithRole('KASATKER', $satker);
        $visible = $this->createDocument($subbidang);
        $hidden = $this->createDocument($otherSubbidang);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dokumen')
            ->assertOk()
            ->assertJsonFragment(['id' => $visible->id])
            ->assertJsonMissing(['id' => $hidden->id]);

        $this->getJson("/api/v1/dokumen/{$hidden->id}")
            ->assertForbidden();
    }

    public function test_super_admin_can_read_all_documents_but_cannot_upload(): void
    {
        $unitA = $this->createUnit('SUBBIDANG');
        $unitB = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('SUPER_ADMIN');
        $documentA = $this->createDocument($unitA);
        $documentB = $this->createDocument($unitB);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dokumen')
            ->assertOk()
            ->assertJsonFragment(['id' => $documentA->id])
            ->assertJsonFragment(['id' => $documentB->id]);

        $this->postJson('/api/v1/dokumen', [
            'unit_pemilik_id' => $unitA->id,
            'judul' => 'Upload oleh Super Admin',
            'jenis' => 'FOTO_KONDISI',
            'file' => UploadedFile::fake()->create(
                'dokumen.pdf',
                100,
                'application/pdf'
            ),
        ])->assertForbidden();
    }

    public function test_staff_cannot_access_document_api(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('STAFF', $unit);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/dokumen')
            ->assertForbidden();
    }

    public function test_upload_rejects_unsupported_file_type(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/dokumen', [
            'unit_pemilik_id' => $unit->id,
            'judul' => 'File Tidak Valid',
            'jenis' => 'FOTO_KONDISI',
            'file' => UploadedFile::fake()->create(
                'malware.exe',
                100,
                'application/octet-stream'
            ),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('dokumen', 0);
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

        $role = Role::query()
            ->where('kode', $roleCode)
            ->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unit?->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function createDocument(UnitOrganisasi $unit): Dokumen
    {
        $storageKey = 'dokumen/'.$unit->id.'/'.fake()->uuid().'.pdf';

        Storage::disk('local')->put(
            $storageKey,
            '%PDF-1.4 test document'
        );

        return Dokumen::query()->create([
            'unit_pemilik_id' => $unit->id,
            'dibuat_oleh' => User::query()->firstOrFail()->id,
            'kode' => 'DOC-'.fake()->unique()->uuid(),
            'judul' => 'Dokumen Pengujian',
            'jenis' => 'FOTO_KONDISI',
            'nama_file_asli' => 'dokumen.pdf',
            'storage_key' => $storageKey,
            'ekstensi' => 'pdf',
            'mime_type' => 'application/pdf',
            'ukuran_byte' => 100,
            'checksum_sha256' => hash(
                'sha256',
                '%PDF-1.4 test document'
            ),
            'status_pemeriksaan' => 'MENUNGGU_PEMERIKSAAN',
            'dibuat_pada' => now(),
        ]);
    }
}
