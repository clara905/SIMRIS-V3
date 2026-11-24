<?php

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MetodeRisikoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    private int $methodVersionId;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Pengujian integritas langsung hanya dijalankan pada MySQL DB-5.');
        }

        $this->assertSame('simris_db5_test', DB::connection()->getDatabaseName());
        $this->assertTrue(
            DB::table('migrations')
                ->where('migration', '2026_11_23_073336_add_unique_risk_type_to_penilaian_risiko_table')
                ->exists(),
            'Migration DB-5 untuk unique jenis penilaian belum diterapkan.'
        );

        DB::beginTransaction();
        $this->seed(DatabaseSeeder::class);

        $methodVersionId = DB::table('versi_metode_risiko')
            ->where('kode_versi', 'SIMRIS-MR-001')
            ->value('id');

        $this->assertNotNull($methodVersionId);
        $this->methodVersionId = (int) $methodVersionId;
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_user_factory_and_seeders_match_schema_and_preserve_existing_data(): void
    {
        $user = UserFactory::new()->create();
        $userBeforeSeed = DB::table('users')->where('id', $user->id)->first();
        $countsBefore = $this->masterCounts();

        $roleId = DB::table('roles')->where('kode', 'SUPER_ADMIN')->value('id');
        $versionId = $this->methodVersionId;
        $probabilityId = DB::table('skala_kemungkinan')
            ->where('versi_metode_id', $versionId)
            ->where('nilai', 1)
            ->value('id');
        $impactId = DB::table('skala_dampak')
            ->where('versi_metode_id', $versionId)
            ->where('nilai', 1)
            ->value('id');
        $matrixId = DB::table('matriks_risiko')
            ->where('versi_metode_id', $versionId)
            ->where('kemungkinan_id', $probabilityId)
            ->where('dampak_id', $impactId)
            ->value('id');
        $sourceId = DB::table('sumber_ancaman')->where('kode', 'S')->value('id');

        DB::table('roles')->where('id', $roleId)->update(['nama' => 'Nilai manual DB-5']);
        DB::table('versi_metode_risiko')->where('id', $versionId)->update(['nama' => 'Metode manual DB-5']);
        DB::table('skala_kemungkinan')->where('id', $probabilityId)->update(['nama' => 'Kemungkinan manual DB-5']);
        DB::table('skala_dampak')->where('id', $impactId)->update(['nama' => 'Dampak manual DB-5']);
        DB::table('matriks_risiko')->where('id', $matrixId)->update(['rekomendasi_keputusan' => 'Rekomendasi manual DB-5']);
        DB::table('sumber_ancaman')->where('id', $sourceId)->update(['nama' => 'Sumber manual DB-5']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($countsBefore, $this->masterCounts());
        $this->assertEquals(
            $userBeforeSeed,
            DB::table('users')->where('id', $user->id)->first()
        );
        $this->assertDatabaseHas('roles', ['id' => $roleId, 'nama' => 'Nilai manual DB-5']);
        $this->assertDatabaseHas('versi_metode_risiko', ['id' => $versionId, 'nama' => 'Metode manual DB-5']);
        $this->assertDatabaseHas('skala_kemungkinan', ['id' => $probabilityId, 'nama' => 'Kemungkinan manual DB-5']);
        $this->assertDatabaseHas('skala_dampak', ['id' => $impactId, 'nama' => 'Dampak manual DB-5']);
        $this->assertDatabaseHas('matriks_risiko', ['id' => $matrixId, 'rekomendasi_keputusan' => 'Rekomendasi manual DB-5']);
        $this->assertDatabaseHas('sumber_ancaman', ['id' => $sourceId, 'nama' => 'Sumber manual DB-5']);
        $this->assertArrayNotHasKey('password', (array) $userBeforeSeed);
    }

    public function test_frozen_method_seeder_fails_without_modifying_master_data(): void
    {
        $version = DB::table('versi_metode_risiko')->where('id', $this->methodVersionId)->first();
        $countsBefore = $this->masterCounts();

        DB::table('versi_metode_risiko')
            ->where('id', $this->methodVersionId)
            ->update(['dibekukan' => true, 'dibekukan_pada' => now()]);

        $exception = $this->expectRuntimeException(
            fn () => $this->seed(MetodeRisikoSeeder::class)
        );

        $this->assertStringContainsString('sudah dibekukan', $exception->getMessage());
        $this->assertSame($countsBefore, $this->masterCounts());
        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $this->methodVersionId,
            'nama' => $version->nama,
            'dibekukan' => true,
        ]);
    }

    public function test_unit_employee_and_placement_foreign_keys_restrict_deletes(): void
    {
        $rootUnitId = $this->insertUnit('SATKER');
        $childUnitId = $this->insertUnit('BIDANG', $rootUnitId);
        $pegawaiId = $this->insertPegawai();
        $user = UserFactory::new()->create(['pegawai_id' => $pegawaiId]);

        $this->insertPlacement($pegawaiId, $childUnitId, '2024-01-01', '2024-12-31');
        $this->insertPlacement($pegawaiId, $rootUnitId, '2025-01-01', null);

        $this->assertSame(2, DB::table('penempatan_pegawai')->where('pegawai_id', $pegawaiId)->count());

        $this->expectDatabaseFailure(fn () => $this->insertPlacement(
            $pegawaiId,
            PHP_INT_MAX,
            '2026-01-01',
            null
        ));

        $this->expectDatabaseFailure(fn () => DB::table('unit_organisasi')->where('id', $rootUnitId)->delete());
        $this->expectDatabaseFailure(fn () => DB::table('pegawai')->where('id', $pegawaiId)->delete());

        $this->assertDatabaseHas('unit_organisasi', ['id' => $rootUnitId]);
        $this->assertDatabaseHas('pegawai', ['id' => $pegawaiId]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'pegawai_id' => $pegawaiId]);

        $this->insertPlacement($pegawaiId, $childUnitId, '2024-06-01', '2024-08-31');
        $this->assertSame(
            3,
            DB::table('penempatan_pegawai')->where('pegawai_id', $pegawaiId)->count(),
            'Database saat ini tidak mencegah periode penempatan yang tumpang tindih.'
        );
    }

    public function test_unit_type_check_and_parent_foreign_key_do_not_enforce_hierarchy_types(): void
    {
        $rootId = $this->insertUnit('SATKER');
        $divisionId = $this->insertUnit('BIDANG', $rootId);
        $sectionId = $this->insertUnit('BAGIAN', $rootId);
        $subdivisionId = $this->insertUnit('SUBBIDANG', $divisionId);
        $subsectionId = $this->insertUnit('SUBBAGIAN', $sectionId);

        $this->assertDatabaseHas('unit_organisasi', ['id' => $subdivisionId, 'parent_id' => $divisionId]);
        $this->assertDatabaseHas('unit_organisasi', ['id' => $subsectionId, 'parent_id' => $sectionId]);
        $this->expectDatabaseFailure(fn () => $this->insertUnit('SUBBIDANG', PHP_INT_MAX));
        $this->expectDatabaseFailure(fn () => $this->insertUnit('INVALID'));

        $wrongParentTypeId = $this->insertUnit('SUBBIDANG', $sectionId);
        $this->assertDatabaseHas('unit_organisasi', [
            'id' => $wrongParentTypeId,
            'parent_id' => $sectionId,
        ]);
    }

    public function test_risk_code_scope_and_asset_snapshots_are_enforced(): void
    {
        $base = $this->createDomainBase();
        $assessmentA = $this->createAssessment($base, 1, $this->methodVersionId);
        $assessmentB = $this->createAssessment($base, 2, $this->methodVersionId, $assessmentA);
        $riskA = $this->createRisk($base, $assessmentA, 'DB5-SHARED-CODE');
        $riskB = $this->createRisk($base, $assessmentB, 'DB5-SHARED-CODE');

        $this->assertNotSame($riskA, $riskB);
        $this->expectDatabaseFailure(fn () => $this->createRisk($base, $assessmentA, 'DB5-SHARED-CODE'));

        $assetA = $this->createAsset($base['unit_id']);
        $assetB = $this->createAsset($base['unit_id']);
        $riskAssetA = $this->insertRiskAsset($riskA, $assetA, 'snapshot-a');
        $this->insertRiskAsset($riskA, $assetB, 'snapshot-b');
        $this->insertRiskAsset($riskB, $assetA, 'snapshot-c');

        $this->assertSame(2, DB::table('risiko_aset')->where('risiko_id', $riskA)->count());
        $this->assertSame(2, DB::table('risiko_aset')->where('aset_idx', $assetA)->count());
        $this->expectDatabaseFailure(fn () => $this->insertRiskAsset($riskA, $assetA, 'duplicate'));

        DB::table('aset')->where('idx', $assetA)->update(['nama' => 'Nama aset terbaru']);
        $this->assertDatabaseHas('risiko_aset', [
            'id' => $riskAssetA,
            'nama_aset_snapshot' => 'Nama aset snapshot-a',
        ]);
        $this->expectDatabaseFailure(fn () => DB::table('aset')->where('idx', $assetA)->delete());
        $this->assertDatabaseHas('aset', ['idx' => $assetA, 'nama' => 'Nama aset terbaru']);
    }

    public function test_assessment_type_and_method_component_constraints_are_enforced(): void
    {
        $base = $this->createDomainBase();
        $assessmentA = $this->createAssessment($base, 1, $this->methodVersionId);
        $riskA = $this->createRisk($base, $assessmentA, 'DB5-RATING-A');
        $matrixA = DB::table('matriks_risiko')
            ->where('versi_metode_id', $this->methodVersionId)
            ->first();

        $newMethod = $this->createMethodVersionWithOneMatrix();
        $assessmentB = $this->createAssessment($base, 2, $newMethod['version_id'], $assessmentA);
        $this->assertDatabaseHas('asesmen_risiko', [
            'id' => $assessmentA,
            'versi_metode_id' => $this->methodVersionId,
        ]);

        $this->expectDatabaseFailure(fn () => DB::table('matriks_risiko')->insert([
            'versi_metode_id' => $this->methodVersionId,
            'kemungkinan_id' => $newMethod['probability_id'],
            'dampak_id' => $matrixA->dampak_id,
            'tingkat_risiko_id' => $matrixA->tingkat_risiko_id,
            'skor' => 999,
        ]));

        $this->expectDatabaseFailure(fn () => DB::table('matriks_risiko')->insert([
            'versi_metode_id' => $this->methodVersionId,
            'kemungkinan_id' => $matrixA->kemungkinan_id,
            'dampak_id' => $matrixA->dampak_id,
            'tingkat_risiko_id' => $matrixA->tingkat_risiko_id,
            'skor' => $matrixA->skor,
        ]));

        $inherentId = $this->insertRating($riskA, (int) $matrixA->id, (int) $base['user_id'], 'INHEREN');
        $residualId = $this->insertRating($riskA, (int) $matrixA->id, (int) $base['user_id'], 'RESIDUAL');

        $this->assertDatabaseHas('penilaian_risiko', ['id' => $inherentId, 'jenis_penilaian' => 'INHEREN']);
        $this->assertDatabaseHas('penilaian_risiko', ['id' => $residualId, 'jenis_penilaian' => 'RESIDUAL']);
        $this->expectDatabaseFailure(fn () => $this->insertRating(
            $riskA,
            (int) $matrixA->id,
            (int) $base['user_id'],
            'INHEREN'
        ));
        $this->expectDatabaseFailure(fn () => $this->insertRating(
            $riskA,
            (int) $matrixA->id,
            (int) $base['user_id'],
            'FINAL'
        ));

        $riskWithCrossVersionMatrix = $this->createRisk($base, $assessmentA, 'DB5-CROSS-VERSION');
        $crossVersionRatingId = $this->insertRating(
            $riskWithCrossVersionMatrix,
            $newMethod['matrix_id'],
            (int) $base['user_id'],
            'INHEREN'
        );

        $this->assertDatabaseHas('penilaian_risiko', [
            'id' => $crossVersionRatingId,
            'risiko_id' => $riskWithCrossVersionMatrix,
            'matriks_id' => $newMethod['matrix_id'],
        ]);
        $this->assertSame(1, DB::table('matriks_risiko')->where('versi_metode_id', $newMethod['version_id'])->count());

        DB::table('versi_metode_risiko')
            ->where('id', $this->methodVersionId)
            ->update(['dibekukan' => true, 'dibekukan_pada' => now()]);
        DB::table('versi_metode_risiko')
            ->where('id', $this->methodVersionId)
            ->update(['nama' => 'Perubahan langsung setelah dibekukan']);

        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $this->methodVersionId,
            'nama' => 'Perubahan langsung setelah dibekukan',
            'dibekukan' => true,
        ]);
        $this->assertDatabaseHas('asesmen_risiko', [
            'id' => $assessmentB,
            'versi_metode_id' => $newMethod['version_id'],
        ]);
    }

    public function test_database_foreign_keys_use_restrict_and_domain_tables_have_no_laravel_timestamps(): void
    {
        $ratingIndexes = DB::select(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ?',
            ['penilaian_risiko']
        );
        $ratingIndexNames = array_unique(array_map(
            static fn (object $index): string => $index->INDEX_NAME,
            $ratingIndexes
        ));
        $nonRestrictingForeignKeys = DB::select("
            SELECT CONSTRAINT_NAME, DELETE_RULE
            FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND DELETE_RULE NOT IN ('RESTRICT', 'NO ACTION')
        ");
        $tablesWithoutPrimaryKeys = DB::select("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES AS tables_in_schema
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_TYPE = 'BASE TABLE'
                AND NOT EXISTS (
                    SELECT 1
                    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS table_constraints
                    WHERE table_constraints.CONSTRAINT_SCHEMA = tables_in_schema.TABLE_SCHEMA
                        AND table_constraints.TABLE_NAME = tables_in_schema.TABLE_NAME
                        AND table_constraints.CONSTRAINT_TYPE = 'PRIMARY KEY'
                )
        ");
        $domainTimestamps = DB::select("
            SELECT TABLE_NAME, COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND COLUMN_NAME IN ('created_at', 'updated_at')
                AND TABLE_NAME NOT IN (
                    'cache', 'cache_locks', 'failed_jobs', 'job_batches',
                    'jobs', 'migrations', 'password_reset_tokens',
                    'personal_access_tokens', 'sessions'
                )
        ");

        $this->assertContains('uq_penilaian_risiko_risiko_jenis', $ratingIndexNames);
        $this->assertNotContains('idx_penilaian_risiko_jenis', $ratingIndexNames);
        $this->assertCount(0, $nonRestrictingForeignKeys);
        $this->assertCount(0, $tablesWithoutPrimaryKeys);
        $this->assertCount(0, $domainTimestamps);
    }

    /**
     * @return array<string, int>
     */
    private function masterCounts(): array
    {
        return [
            'roles' => DB::table('roles')->count(),
            'versions' => DB::table('versi_metode_risiko')->count(),
            'probability_scales' => DB::table('skala_kemungkinan')->count(),
            'impact_scales' => DB::table('skala_dampak')->count(),
            'risk_levels' => DB::table('tingkat_risiko')->count(),
            'matrices' => DB::table('matriks_risiko')->count(),
            'threat_sources' => DB::table('sumber_ancaman')->count(),
        ];
    }

    private function insertUnit(string $type, ?int $parentId = null): int
    {
        return (int) DB::table('unit_organisasi')->insertGetId([
            'parent_id' => $parentId,
            'kode' => $this->uniqueCode('UNIT'),
            'nama' => 'Unit DB-5',
            'jenis' => $type,
            'aktif' => true,
        ]);
    }

    private function insertPegawai(): int
    {
        return (int) DB::table('pegawai')->insertGetId([
            'nip' => $this->uniqueCode('NIP'),
            'nama' => 'Pegawai DB-5',
            'email_kontak' => null,
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);
    }

    private function insertPlacement(int $pegawaiId, int $unitId, string $start, ?string $end): void
    {
        DB::table('penempatan_pegawai')->insert([
            'pegawai_id' => $pegawaiId,
            'unit_id' => $unitId,
            'mulai' => $start,
            'selesai' => $end,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function createDomainBase(): array
    {
        $unitId = $this->insertUnit('SATKER');
        $pegawaiId = $this->insertPegawai();
        $user = UserFactory::new()->create(['pegawai_id' => $pegawaiId]);
        $seriesId = (int) DB::table('seri_asesmen')->insertGetId([
            'kode' => $this->uniqueCode('SERIES'),
            'nama' => 'Seri DB-5',
        ]);
        $threatTypeId = (int) DB::table('jenis_ancaman')->insertGetId([
            'kode' => $this->uniqueCode('THREAT-TYPE'),
            'nama' => 'Jenis ancaman DB-5',
        ]);
        $threatId = (int) DB::table('ancaman')->insertGetId([
            'jenis_ancaman_id' => $threatTypeId,
            'kode' => $this->uniqueCode('THREAT'),
            'nama' => 'Ancaman DB-5',
            'deskripsi' => 'Fixture untuk validasi integritas database.',
        ]);
        $vulnerabilityTypeId = (int) DB::table('jenis_kerentanan')->insertGetId([
            'kode' => $this->uniqueCode('VULN-TYPE'),
            'nama' => 'Jenis kerentanan DB-5',
        ]);
        $vulnerabilityId = (int) DB::table('kerentanan')->insertGetId([
            'jenis_kerentanan_id' => $vulnerabilityTypeId,
            'kode' => $this->uniqueCode('VULN'),
            'nama' => 'Kerentanan DB-5',
            'deskripsi' => 'Fixture untuk validasi integritas database.',
        ]);
        $sourceThreatId = (int) DB::table('sumber_ancaman')
            ->where('kode', 'S')
            ->value('id');

        return [
            'unit_id' => $unitId,
            'pegawai_id' => $pegawaiId,
            'user_id' => (int) $user->id,
            'series_id' => $seriesId,
            'threat_id' => $threatId,
            'vulnerability_id' => $vulnerabilityId,
            'source_threat_id' => $sourceThreatId,
        ];
    }

    private function createAssessment(array $base, int $number, int $methodVersionId, ?int $previousId = null): int
    {
        $periodId = (int) DB::table('periode_penilaian')->insertGetId([
            'unit_id' => $base['unit_id'],
            'dibuat_oleh' => $base['user_id'],
            'nama_periode' => $this->uniqueCode('PERIOD'),
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'DRAFT',
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ]);

        return (int) DB::table('asesmen_risiko')->insertGetId([
            'seri_asesmen_id' => $base['series_id'],
            'versi_sebelumnya_id' => $previousId,
            'periode_id' => $periodId,
            'unit_id' => $base['unit_id'],
            'versi_metode_id' => $methodVersionId,
            'dibuat_oleh' => $base['user_id'],
            'tujuan' => 'Tujuan asesmen DB-5',
            'ruang_lingkup' => 'Ruang lingkup asesmen DB-5',
            'status' => 'DRAFT',
            'nomor_versi' => $number,
            'dibuat_pada' => now(),
            'dibekukan_pada' => null,
        ]);
    }

    private function createRisk(array $base, int $assessmentId, string $code): int
    {
        return (int) DB::table('risiko')->insertGetId([
            'asesmen_id' => $assessmentId,
            'ancaman_id' => $base['threat_id'],
            'sumber_ancaman_id' => $base['source_threat_id'],
            'kerentanan_id' => $base['vulnerability_id'],
            'pemilik_risiko_id' => $base['pegawai_id'],
            'dibuat_oleh' => $base['user_id'],
            'kode' => $code,
            'skenario' => 'Skenario risiko DB-5',
            'uraian_kerentanan' => 'Uraian kerentanan DB-5',
            'uraian_dampak' => 'Uraian dampak DB-5',
            'arah_dampak' => true,
            'dibuat_pada' => now(),
            'uraian_pelaksanaan_rencana_kerja' => null,
            'uraian_penerapan_sop_kontrol' => null,
        ]);
    }

    private function createAsset(int $unitId): int
    {
        $sourceId = (int) DB::table('sumber_aset')->insertGetId([
            'kode' => $this->uniqueCode('ASSET-SOURCE'),
            'nama' => 'Sumber aset DB-5',
        ]);
        $categoryId = (int) DB::table('kategori_aset')->insertGetId([
            'parent_id' => null,
            'kode' => $this->uniqueCode('ASSET-CATEGORY'),
            'nama' => 'Kategori aset DB-5',
        ]);
        $classificationId = (int) DB::table('klasifikasi_informasi')->insertGetId([
            'kode' => $this->uniqueCode('CLASSIFICATION'),
            'nama' => 'Klasifikasi DB-5',
            'deskripsi' => 'Klasifikasi untuk fixture DB-5.',
        ]);

        return (int) DB::table('aset')->insertGetId([
            'sumber_id' => $sourceId,
            'external_id' => null,
            'unit_pemilik_id' => $unitId,
            'kategori_id' => $categoryId,
            'klasifikasi_informasi_id' => $classificationId,
            'kode_aset' => $this->uniqueCode('ASSET'),
            'nama' => 'Nama aset snapshot-a',
            'deskripsi' => 'Aset fixture DB-5.',
            'merk' => 'SIMRIS',
            'nomor_seri' => null,
            'jumlah' => 1,
            'kondisi' => 'BAIK',
            'lokasi' => 'Unit DB-5',
            'tanggal_pengadaan' => '2026-01-01',
            'status' => 'AKTIF',
            'last_synced_at' => null,
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ]);
    }

    private function insertRiskAsset(int $riskId, int $assetId, string $snapshotSuffix): int
    {
        return (int) DB::table('risiko_aset')->insertGetId([
            'risiko_id' => $riskId,
            'aset_idx' => $assetId,
            'kode_aset_snapshot' => 'CODE-'.$snapshotSuffix,
            'nama_aset_snapshot' => 'Nama aset '.$snapshotSuffix,
            'kategori_snapshot' => 'Kategori '.$snapshotSuffix,
            'klasifikasi_snapshot' => 'Klasifikasi '.$snapshotSuffix,
            'unit_pemilik_snapshot' => 'Unit '.$snapshotSuffix,
            'lokasi_snapshot' => 'Lokasi '.$snapshotSuffix,
            'kondisi_snapshot' => 'BAIK',
            'direkam_pada' => now(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function createMethodVersionWithOneMatrix(): array
    {
        $versionId = (int) DB::table('versi_metode_risiko')->insertGetId([
            'kode_versi' => $this->uniqueCode('METHOD'),
            'nama' => 'Metode DB-5 baru',
            'deskripsi' => 'Fixture versi metode baru.',
            'dibekukan' => false,
            'dibekukan_pada' => null,
        ]);
        $probabilityId = (int) DB::table('skala_kemungkinan')->insertGetId([
            'versi_metode_id' => $versionId,
            'nilai' => 1,
            'nama' => 'Kemungkinan DB-5 baru',
            'deskripsi' => null,
        ]);
        $impactId = (int) DB::table('skala_dampak')->insertGetId([
            'versi_metode_id' => $versionId,
            'nilai' => 1,
            'nama' => 'Dampak DB-5 baru',
            'deskripsi' => null,
        ]);
        $riskLevelId = (int) DB::table('tingkat_risiko')->insertGetId([
            'versi_metode_id' => $versionId,
            'kode' => 'RENDAH',
            'nama' => 'Rendah DB-5',
            'urutan' => 1,
        ]);
        $matrixId = (int) DB::table('matriks_risiko')->insertGetId([
            'versi_metode_id' => $versionId,
            'kemungkinan_id' => $probabilityId,
            'dampak_id' => $impactId,
            'tingkat_risiko_id' => $riskLevelId,
            'skor' => 1,
            'rekomendasi_keputusan' => 'TERIMA',
        ]);

        return [
            'version_id' => $versionId,
            'probability_id' => $probabilityId,
            'matrix_id' => $matrixId,
        ];
    }

    private function insertRating(int $riskId, int $matrixId, int $userId, string $type): int
    {
        return (int) DB::table('penilaian_risiko')->insertGetId([
            'risiko_id' => $riskId,
            'matriks_id' => $matrixId,
            'dinilai_oleh' => $userId,
            'jenis_penilaian' => $type,
            'alasan_kemungkinan' => 'Alasan kemungkinan DB-5',
            'alasan_dampak' => 'Alasan dampak DB-5',
            'kesimpulan' => 'Kesimpulan DB-5',
            'dinilai_pada' => now(),
        ]);
    }

    private function uniqueCode(string $prefix): string
    {
        return $prefix.'-'.strtoupper(bin2hex(random_bytes(6)));
    }

    private function expectDatabaseFailure(callable $operation): QueryException
    {
        try {
            $operation();
        } catch (QueryException $exception) {
            return $exception;
        }

        $this->fail('Database seharusnya menolak operasi yang melanggar constraint.');
    }

    private function expectRuntimeException(callable $operation): RuntimeException
    {
        try {
            $operation();
        } catch (RuntimeException $exception) {
            return $exception;
        }

        $this->fail('Seeder seharusnya gagal secara aman untuk metode yang telah dibekukan.');
    }
}
