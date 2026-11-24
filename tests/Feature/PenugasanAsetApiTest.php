<?php

namespace Tests\Feature;

use App\Models\Aset;
use App\Models\KategoriAset;
use App\Models\KlasifikasiInformasi;
use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PenugasanAset;
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

class PenugasanAsetApiTest extends TestCase
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

    public function test_kasub_can_create_and_end_asset_assignment(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
        ])
            ->assertCreated()
            ->assertJsonPath('data.pegawai.id', $pegawai->id)
            ->assertJsonPath('data.status', 'AKTIF');

        $assignment = PenugasanAset::query()->firstOrFail();

        $this->patchJson(
            "/api/v1/penugasan-aset/{$assignment->id}/akhiri",
            ['tanggal_selesai' => '2026-06-30']
        )
            ->assertOk()
            ->assertJsonPath('data.selesai', '2026-06-30')
            ->assertJsonPath('data.status', 'SELESAI');

        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => '2026-06-30 00:00:00',
        ]);
    }

    public function test_assignment_creation_and_ending_are_audited(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
        ])->assertCreated();

        $assignment = PenugasanAset::query()->firstOrFail();

        $createAudit = LogAudit::query()
            ->where('aksi', 'CREATE_PENUGASAN_ASET')
            ->firstOrFail();

        $this->assertSame('penugasan_aset', $createAudit->entitas);
        $this->assertSame($assignment->id, $createAudit->entitas_id);
        $this->assertNull($createAudit->data_lama);
        $this->assertSame(
            $pegawai->id,
            $createAudit->data_baru['pegawai_id']
        );

        $this->patchJson(
            "/api/v1/penugasan-aset/{$assignment->id}/akhiri",
            ['tanggal_selesai' => '2026-06-30']
        )->assertOk();

        $endAudit = LogAudit::query()
            ->where('aksi', 'END_PENUGASAN_ASET')
            ->firstOrFail();

        $this->assertSame($assignment->id, $endAudit->entitas_id);
        $this->assertNull($endAudit->data_lama['selesai']);
        $this->assertSame('2026-06-30', $endAudit->data_baru['selesai']);
    }

    public function test_assignment_creation_is_rolled_back_when_audit_fails(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        Sanctum::actingAs($user);

        $this->mock(AuditService::class, function ($mock) {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Audit gagal.'));
        });

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
        ])->assertInternalServerError();

        $this->assertDatabaseCount('penugasan_aset', 0);
        $this->assertDatabaseCount('log_audit', 0);
    }

    public function test_assignment_ending_is_rolled_back_when_audit_fails(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);

        $pegawai = $this->createEmployeeWithPlacement(
            $unit,
            '2026-01-01',
            null
        );

        $assignment = $this->createAssignment(
            $aset,
            $pegawai,
            '2026-01-15'
        );

        Sanctum::actingAs($user);

        $this->mock(AuditService::class, function ($mock) {
            $mock->shouldReceive('record')
                ->once()
                ->andThrow(new RuntimeException('Audit gagal.'));
        });

        $this->patchJson(
            "/api/v1/penugasan-aset/{$assignment->id}/akhiri",
            ['tanggal_selesai' => '2026-06-30']
        )->assertInternalServerError();

        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => null,
        ]);

        $this->assertDatabaseCount('log_audit', 0);
    }

    public function test_assignment_period_must_be_fully_covered_by_employee_placement(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement(
            $unit,
            '2026-01-01',
            '2026-06-30'
        );

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
            'selesai' => '2026-07-01',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pegawai_id');

        $this->assertDatabaseCount('penugasan_aset', 0);
    }

    public function test_employee_must_be_placed_in_asset_owner_unit(): void
    {
        $unitA = $this->createUnit('SUBBIDANG');
        $unitB = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $unitA);
        $aset = $this->createAsset($unitA);
        $pegawai = $this->createEmployeeWithPlacement($unitB);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pegawai_id');
    }

    public function test_inactive_employee_cannot_receive_asset_assignment(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);
        $pegawai->update(['aktif' => false]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pegawai_id');
    }

    public function test_same_responsibility_cannot_have_overlapping_assignments(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        Sanctum::actingAs($user);

        $payload = [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => '2026-01-15',
            'selesai' => '2026-06-30',
        ];

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", $payload)
            ->assertCreated();

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            ...$payload,
            'mulai' => '2026-06-01',
            'selesai' => '2026-07-15',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('jenis_tanggung_jawab');

        $this->assertDatabaseCount('penugasan_aset', 1);
    }

    public function test_different_responsibility_types_can_be_assigned_separately(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        Sanctum::actingAs($user);

        foreach (['PENANGGUNG_JAWAB', 'PENGGUNA'] as $jenis) {
            $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
                'pegawai_id' => $pegawai->id,
                'jenis_tanggung_jawab' => $jenis,
                'mulai' => '2026-01-15',
            ])->assertCreated();
        }

        $this->assertDatabaseCount('penugasan_aset', 2);
    }

    public function test_kabid_can_view_assignment_history_but_cannot_create(): void
    {
        $bidang = $this->createUnit('BIDANG');
        $unit = $this->createUnit('SUBBIDANG', $bidang->id);
        $user = $this->createUserWithRole('KABID', $bidang);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement($unit);

        $assignment = $this->createAssignment($aset, $pegawai);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aset/{$aset->idx}/penugasan")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $assignment->id,
            ]);

        $this->postJson("/api/v1/aset/{$aset->idx}/penugasan", [
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENGGUNA',
            'mulai' => '2026-01-15',
        ])->assertForbidden();
    }

    public function test_kasub_cannot_view_assignment_history_from_another_unit(): void
    {
        $ownUnit = $this->createUnit('SUBBIDANG');
        $otherUnit = $this->createUnit('SUBBIDANG');

        $user = $this->createUserWithRole('KASUB', $ownUnit);
        $aset = $this->createAsset($otherUnit);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aset/{$aset->idx}/penugasan")
            ->assertForbidden();
    }

    public function test_ended_assignment_cannot_be_ended_again(): void
    {
        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement(
            $unit,
            '2026-01-01',
            '2026-06-30'
        );

        $assignment = $this->createAssignment(
            $aset,
            $pegawai,
            '2026-01-15',
            '2026-06-30'
        );

        Sanctum::actingAs($user);

        $this->patchJson(
            "/api/v1/penugasan-aset/{$assignment->id}/akhiri",
            ['tanggal_selesai' => '2026-06-30']
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_selesai');
    }

    public function test_assignment_with_future_end_date_remains_active_until_that_date_passes(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement(
            $unit,
            '2026-01-01'
        );
        $assignment = $this->createAssignment(
            $aset,
            $pegawai,
            '2026-01-15',
            '2026-12-01'
        );

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/aset/{$aset->idx}/penugasan")
            ->assertOk()
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.status', 'AKTIF');

        $this->travelTo('2026-12-02 00:00:00');

        $this->getJson("/api/v1/aset/{$aset->idx}/penugasan")
            ->assertOk()
            ->assertJsonPath('data.0.status', 'SELESAI');
    }

    public function test_future_assignment_end_date_can_be_corrected_before_it_is_reached(): void
    {
        $this->travelTo('2026-11-18 10:00:00');

        $unit = $this->createUnit('SUBBIDANG');
        $user = $this->createUserWithRole('KASUB', $unit);
        $aset = $this->createAsset($unit);
        $pegawai = $this->createEmployeeWithPlacement(
            $unit,
            '2026-01-01',
            '2026-12-01'
        );
        $assignment = $this->createAssignment(
            $aset,
            $pegawai,
            '2026-01-15',
            '2026-12-01'
        );

        Sanctum::actingAs($user);

        $this->patchJson(
            "/api/v1/penugasan-aset/{$assignment->id}/akhiri",
            ['tanggal_selesai' => '2026-11-25']
        )
            ->assertOk()
            ->assertJsonPath('data.selesai', '2026-11-25')
            ->assertJsonPath('data.status', 'AKTIF');

        $this->assertDatabaseHas('penugasan_aset', [
            'id' => $assignment->id,
            'selesai' => '2026-11-25 00:00:00',
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

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => Role::where('kode', $roleCode)->firstOrFail()->id,
            'unit_id' => $unit?->id,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function createEmployeeWithPlacement(
        UnitOrganisasi $unit,
        string $mulai = '2026-01-01',
        ?string $selesai = null
    ): Pegawai {
        $pegawai = Pegawai::create([
            'nip' => fake()->unique()->numerify('################'),
            'nama' => fake()->name(),
            'email_kontak' => fake()->safeEmail(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);

        PenempatanPegawai::create([
            'pegawai_id' => $pegawai->id,
            'unit_id' => $unit->id,
            'mulai' => $mulai,
            'selesai' => $selesai,
        ]);

        return $pegawai;
    }

    private function createAsset(UnitOrganisasi $unit): Aset
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

        return Aset::query()->create([
            'sumber_id' => $sumber->id,
            'unit_pemilik_id' => $unit->id,
            'kategori_id' => $kategori->id,
            'klasifikasi_informasi_id' => $klasifikasi->id,
            'kode_aset' => fake()->unique()->bothify('AST-####'),
            'nama' => 'Laptop Pengujian',
            'deskripsi' => 'Aset untuk pengujian penugasan.',
            'merk' => 'Lenovo',
            'nomor_seri' => fake()->unique()->bothify('SN-######'),
            'jumlah' => 1,
            'kondisi' => 'BAIK',
            'lokasi' => 'Ruang Kerja',
            'tanggal_pengadaan' => '2026-01-15',
            'status' => 'AKTIF',
        ]);
    }

    private function createAssignment(
        Aset $aset,
        Pegawai $pegawai,
        string $mulai = '2026-01-15',
        ?string $selesai = null
    ): PenugasanAset {
        return PenugasanAset::query()->create([
            'aset_idx' => $aset->idx,
            'pegawai_id' => $pegawai->id,
            'jenis_tanggung_jawab' => 'PENANGGUNG_JAWAB',
            'mulai' => $mulai,
            'selesai' => $selesai,
        ]);
    }
}
