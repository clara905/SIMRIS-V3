<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RencanaPenangananConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_plan_defaults_to_in_progress_and_allows_category_change_until_completion(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        $this->assertDatabaseHas('rencana_penanganan', [
            'id' => $planId,
            'kategori_penanganan' => 'HINDARI',
            'status' => 'SEDANG_DILAKSANAKAN',
        ]);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'kategori_penanganan' => 'MITIGASI',
            ]);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'status' => 'SELESAI',
            ]);

        $this->assertDatabaseHas('rencana_penanganan', [
            'id' => $planId,
            'kategori_penanganan' => 'MITIGASI',
            'status' => 'SELESAI',
        ]);
    }

    public function test_new_plan_accepts_each_business_category(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');

        foreach (['HINDARI', 'MITIGASI', 'ALIH_BAGI', 'TERIMA'] as $category) {
            $planId = $this->createPlan(
                $assessmentId,
                $references['pegawai_id'],
                $category
            );

            $this->assertDatabaseHas('rencana_penanganan', [
                'id' => $planId,
                'kategori_penanganan' => $category,
                'status' => 'SEDANG_DILAKSANAKAN',
            ]);
        }
    }

    public function test_new_plan_rejects_invalid_category(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');

        $this->expectException(QueryException::class);

        $this->createPlan(
            $assessmentId,
            $references['pegawai_id'],
            'INVALID'
        );
    }

    public function test_new_plan_cannot_start_as_completed(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');

        $this->expectException(QueryException::class);

        $this->createPlan(
            $assessmentId,
            $references['pegawai_id'],
            'HINDARI',
            'SELESAI'
        );
    }

    public function test_plan_rejects_status_outside_the_two_allowed_values(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        $this->expectException(QueryException::class);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'status' => 'DRAFT',
            ]);
    }

    public function test_completed_plan_rejects_category_changes(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'status' => 'SELESAI',
            ]);

        $this->expectException(QueryException::class);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'kategori_penanganan' => 'ALIH_BAGI',
            ]);
    }

    public function test_completed_plan_cannot_be_reopened(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'status' => 'SELESAI',
            ]);

        $this->expectException(QueryException::class);

        DB::table('rencana_penanganan')
            ->where('id', $planId)
            ->update([
                'status' => 'SEDANG_DILAKSANAKAN',
            ]);
    }

    public function test_risk_cannot_be_linked_to_a_plan_from_another_assessment(): void
    {
        $references = $this->createDomainReferences();
        $firstAssessmentId = $this->createAssessment($references, 'A');
        $secondAssessmentId = $this->createAssessment($references, 'B');
        $riskId = $this->createRisk($references, $firstAssessmentId);
        $firstPlanId = $this->createPlan(
            $firstAssessmentId,
            $references['pegawai_id']
        );
        $secondPlanId = $this->createPlan(
            $secondAssessmentId,
            $references['pegawai_id']
        );

        DB::table('risiko_penanganan')->insert([
            'asesmen_id' => $firstAssessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $firstPlanId,
        ]);

        $this->assertDatabaseHas('risiko_penanganan', [
            'asesmen_id' => $firstAssessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $firstPlanId,
        ]);

        $this->expectException(QueryException::class);

        DB::table('risiko_penanganan')->insert([
            'asesmen_id' => $firstAssessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $secondPlanId,
        ]);
    }

    public function test_category_rollback_is_rejected_without_changing_plan_data_or_schema_when_plans_exist(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        $migration = require database_path(
            'migrations/2026_11_23_043214_add_kategori_penanganan_and_lifecycle_constraints_to_rencana_penanganan_table.php'
        );

        try {
            $migration->down();
            $this->fail('Rollback semestinya ditolak selama rencana masih ada.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Rollback dibatalkan agar nilai kategori rencana yang sudah tersimpan tidak hilang.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            Schema::hasColumn('rencana_penanganan', 'kategori_penanganan')
        );
        $this->assertDatabaseHas('rencana_penanganan', [
            'id' => $planId,
            'kategori_penanganan' => 'HINDARI',
            'status' => 'SEDANG_DILAKSANAKAN',
        ]);
        $this->assertPlanTriggersExist();
    }

    public function test_category_rollback_proceeds_when_no_plans_exist(): void
    {
        $migration = require database_path(
            'migrations/2026_11_23_043214_add_kategori_penanganan_and_lifecycle_constraints_to_rencana_penanganan_table.php'
        );

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn('rencana_penanganan', 'kategori_penanganan')
        );
        $this->assertFalse(
            $this->planTriggerExists('trg_rencana_penanganan_before_insert')
        );
        $this->assertFalse(
            $this->planTriggerExists('trg_rencana_penanganan_before_update')
        );

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn('rencana_penanganan', 'kategori_penanganan')
        );
        $this->assertPlanTriggersExist();
    }

    public function test_assessment_rollback_is_rejected_without_changing_pivot_schema_or_links_when_plans_exist(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $riskId = $this->createRisk($references, $assessmentId);
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        DB::table('risiko_penanganan')->insert([
            'asesmen_id' => $assessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $planId,
        ]);

        $migration = require database_path(
            'migrations/2026_11_23_043215_enforce_same_assessment_for_risiko_penanganan_table.php'
        );

        try {
            $migration->down();
            $this->fail('Rollback semestinya ditolak selama rencana masih ada.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Rollback ditolak karena tabel rencana_penanganan masih memiliki data. Rollback dapat menghilangkan informasi kategori penanganan.',
                $exception->getMessage()
            );
        }

        $this->assertTrue(
            Schema::hasColumn('risiko_penanganan', 'asesmen_id')
        );
        $this->assertTrue(
            Schema::hasIndex(
                'risiko_penanganan',
                'idx_risiko_penanganan_asesmen_risiko'
            )
        );
        $this->assertTrue(
            Schema::hasIndex(
                'risiko_penanganan',
                'idx_risiko_penanganan_asesmen_rencana'
            )
        );
        $this->assertTrue(
            Schema::hasIndex('risiko', 'uq_risiko_asesmen_id')
        );
        $this->assertTrue(
            Schema::hasIndex(
                'rencana_penanganan',
                'uq_rencana_penanganan_asesmen_id'
            )
        );
        $this->assertPivotAssessmentForeignKeysExist();
        $this->assertPivotPrimaryKeyIsUnchanged();
        $this->assertDatabaseHas('risiko_penanganan', [
            'asesmen_id' => $assessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $planId,
        ]);
    }

    public function test_assessment_rollback_proceeds_when_no_plans_exist(): void
    {
        $migration = require database_path(
            'migrations/2026_11_23_043215_enforce_same_assessment_for_risiko_penanganan_table.php'
        );

        $migration->down();

        $this->assertFalse(
            Schema::hasColumn('risiko_penanganan', 'asesmen_id')
        );
        $this->assertFalse(
            Schema::hasIndex('risiko', 'uq_risiko_asesmen_id')
        );
        $this->assertFalse(
            Schema::hasIndex(
                'rencana_penanganan',
                'uq_rencana_penanganan_asesmen_id'
            )
        );
        $this->assertPivotPrimaryKeyIsUnchanged();
        $this->assertSimplePivotForeignKeysExist();
        $this->assertDatabaseCount('risiko_penanganan', 0);

        $migration->up();

        $this->assertTrue(
            Schema::hasColumn('risiko_penanganan', 'asesmen_id')
        );
        $this->assertPivotAssessmentForeignKeysExist();
        $this->assertPivotPrimaryKeyIsUnchanged();
    }

    public function test_batch_rollback_does_not_partially_revert_when_plans_exist(): void
    {
        $references = $this->createDomainReferences();
        $assessmentId = $this->createAssessment($references, 'A');
        $riskId = $this->createRisk($references, $assessmentId);
        $planId = $this->createPlan($assessmentId, $references['pegawai_id']);

        DB::table('risiko_penanganan')->insert([
            'asesmen_id' => $assessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $planId,
        ]);

        try {
            Artisan::call('migrate:rollback', [
                '--database' => DB::getDefaultConnection(),
                '--step' => 2,
                '--force' => true,
            ]);
            $this->fail('Rollback batch semestinya ditolak selama rencana masih ada.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Rollback ditolak karena tabel rencana_penanganan masih memiliki data. Rollback dapat menghilangkan informasi kategori penanganan.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_11_23_043214_add_kategori_penanganan_and_lifecycle_constraints_to_rencana_penanganan_table',
        ]);
        $this->assertDatabaseHas('migrations', [
            'migration' => '2026_11_23_043215_enforce_same_assessment_for_risiko_penanganan_table',
        ]);
        $this->assertTrue(
            Schema::hasColumn('rencana_penanganan', 'kategori_penanganan')
        );
        $this->assertTrue(
            Schema::hasColumn('risiko_penanganan', 'asesmen_id')
        );
        $this->assertDatabaseHas('risiko_penanganan', [
            'asesmen_id' => $assessmentId,
            'risiko_id' => $riskId,
            'rencana_penanganan_id' => $planId,
        ]);
        $this->assertPivotAssessmentForeignKeysExist();
    }

    private function assertPivotAssessmentForeignKeysExist(): void
    {
        $foreignKeys = $this->getPivotForeignKeys();

        $riskForeignKey = $foreignKeys
            ->where('table', 'risiko')
            ->groupBy('id')
            ->first(function ($columns) {
                $columns = $columns->sortBy('seq');

                return $columns->pluck('from')->all() === [
                    'asesmen_id',
                    'risiko_id',
                ] && $columns->pluck('to')->all() === [
                    'asesmen_id',
                    'id',
                ];
            });

        $planForeignKey = $foreignKeys
            ->where('table', 'rencana_penanganan')
            ->groupBy('id')
            ->first(function ($columns) {
                $columns = $columns->sortBy('seq');

                return $columns->pluck('from')->all() === [
                    'asesmen_id',
                    'rencana_penanganan_id',
                ] && $columns->pluck('to')->all() === [
                    'asesmen_id',
                    'id',
                ];
            });

        $this->assertNotNull($riskForeignKey);
        $this->assertNotNull($planForeignKey);
        $this->assertPivotForeignKeysAreRestrictive($foreignKeys);
    }

    private function assertSimplePivotForeignKeysExist(): void
    {
        $foreignKeys = $this->getPivotForeignKeys();

        $mappings = $foreignKeys
            ->map(fn (object $foreignKey): array => [
                $foreignKey->from,
                $foreignKey->table,
                $foreignKey->to,
            ])
            ->all();

        $this->assertContains(
            ['risiko_id', 'risiko', 'id'],
            $mappings
        );
        $this->assertContains(
            ['rencana_penanganan_id', 'rencana_penanganan', 'id'],
            $mappings
        );
        $this->assertPivotForeignKeysAreRestrictive($foreignKeys);
    }

    /**
     * @return Collection<int, object>
     */
    private function getPivotForeignKeys(): Collection
    {
        if (DB::getDriverName() === 'mysql') {
            return collect(DB::select(
                '
                    SELECT
                        key_usage.CONSTRAINT_NAME AS id,
                        key_usage.ORDINAL_POSITION - 1 AS seq,
                        key_usage.COLUMN_NAME AS source_column,
                        key_usage.REFERENCED_TABLE_NAME AS referenced_table,
                        key_usage.REFERENCED_COLUMN_NAME AS referenced_column,
                        referential_constraints.DELETE_RULE AS delete_rule,
                        referential_constraints.UPDATE_RULE AS update_rule
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS key_usage
                    INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS referential_constraints
                        ON referential_constraints.CONSTRAINT_SCHEMA = key_usage.CONSTRAINT_SCHEMA
                        AND referential_constraints.CONSTRAINT_NAME = key_usage.CONSTRAINT_NAME
                        AND referential_constraints.TABLE_NAME = key_usage.TABLE_NAME
                    WHERE key_usage.CONSTRAINT_SCHEMA = DATABASE()
                        AND key_usage.TABLE_NAME = ?
                        AND key_usage.REFERENCED_TABLE_NAME IS NOT NULL
                    ORDER BY key_usage.CONSTRAINT_NAME, key_usage.ORDINAL_POSITION
                ',
                ['risiko_penanganan']
            ))->map(fn (object $foreignKey): object => (object) [
                'id' => $foreignKey->id,
                'seq' => $foreignKey->seq,
                'from' => $foreignKey->source_column,
                'table' => $foreignKey->referenced_table,
                'to' => $foreignKey->referenced_column,
                'delete_rule' => $foreignKey->delete_rule,
                'update_rule' => $foreignKey->update_rule,
            ]);
        }

        return collect(
            DB::select("PRAGMA foreign_key_list('risiko_penanganan')")
        )->map(fn (object $foreignKey): object => (object) [
            'id' => $foreignKey->id,
            'seq' => $foreignKey->seq,
            'from' => $foreignKey->from,
            'table' => $foreignKey->table,
            'to' => $foreignKey->to,
            'delete_rule' => $foreignKey->on_delete,
            'update_rule' => $foreignKey->on_update,
        ]);
    }

    /**
     * @param  Collection<int, object>  $foreignKeys
     */
    private function assertPivotForeignKeysAreRestrictive(
        Collection $foreignKeys
    ): void {
        foreach ($foreignKeys as $foreignKey) {
            if (DB::getDriverName() === 'mysql') {
                $this->assertSame('RESTRICT', strtoupper($foreignKey->delete_rule));
                $this->assertSame('RESTRICT', strtoupper($foreignKey->update_rule));

                continue;
            }

            $this->assertContains(
                strtoupper($foreignKey->delete_rule),
                ['RESTRICT', 'NO ACTION']
            );
            $this->assertContains(
                strtoupper($foreignKey->update_rule),
                ['RESTRICT', 'NO ACTION']
            );
        }
    }

    private function assertPlanTriggersExist(): void
    {
        foreach ([
            'trg_rencana_penanganan_before_insert',
            'trg_rencana_penanganan_before_update',
        ] as $triggerName) {
            $this->assertTrue(
                $this->planTriggerExists($triggerName),
                "Expected trigger {$triggerName} to exist."
            );
        }
    }

    private function planTriggerExists(string $triggerName): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::selectOne(
                '
                    SELECT TRIGGER_NAME
                    FROM INFORMATION_SCHEMA.TRIGGERS
                    WHERE TRIGGER_SCHEMA = DATABASE()
                        AND TRIGGER_NAME = ?
                ',
                [$triggerName]
            ) !== null;
        }

        return DB::table('sqlite_master')
            ->where('type', 'trigger')
            ->where('name', $triggerName)
            ->exists();
    }

    private function assertPivotPrimaryKeyIsUnchanged(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $primaryKeyColumns = collect(DB::select(
                '
                    SELECT COLUMN_NAME AS name
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                        AND TABLE_NAME = ?
                        AND CONSTRAINT_NAME = ?
                    ORDER BY ORDINAL_POSITION
                ',
                ['risiko_penanganan', 'PRIMARY']
            ))
                ->pluck('name')
                ->all();
        } else {
            $primaryKeyColumns = collect(
                DB::select("PRAGMA table_info('risiko_penanganan')")
            )
                ->filter(fn (object $column): bool => $column->pk > 0)
                ->sortBy('pk')
                ->pluck('name')
                ->all();
        }

        $this->assertSame([
            'risiko_id',
            'rencana_penanganan_id',
        ], $primaryKeyColumns);
    }

    /**
     * @return array{
     *     unit_id: int,
     *     user_id: int,
     *     pegawai_id: int,
     *     versi_metode_id: int,
     *     sumber_ancaman_id: int,
     *     ancaman_id: int,
     *     kerentanan_id: int
     * }
     */
    private function createDomainReferences(): array
    {
        $unitId = DB::table('unit_organisasi')->insertGetId([
            'parent_id' => null,
            'kode' => 'UNIT-UJI',
            'nama' => 'Unit Pengujian',
            'jenis' => 'SATKER',
            'aktif' => true,
        ]);

        $pegawaiId = DB::table('pegawai')->insertGetId([
            'nip' => '1980000000001',
            'nama' => 'Pegawai Pengujian',
            'email_kontak' => null,
            'jenis_kepegawaian' => 'PNS',
            'aktif' => true,
        ]);

        $userId = DB::table('users')->insertGetId([
            'pegawai_id' => $pegawaiId,
            'username' => 'user.uji',
            'email' => 'user.uji@example.test',
            'firebase_uid' => 'firebase-user-uji',
            'aktif' => true,
        ]);

        $versiMetodeId = DB::table('versi_metode_risiko')->insertGetId([
            'kode_versi' => 'METODE-UJI',
            'nama' => 'Metode Pengujian',
            'deskripsi' => null,
            'dibekukan' => false,
            'dibekukan_pada' => null,
        ]);

        $sumberAncamanId = DB::table('sumber_ancaman')->insertGetId([
            'kode' => 'S',
            'nama' => 'Sumber Pengujian',
            'deskripsi' => null,
        ]);

        $jenisAncamanId = DB::table('jenis_ancaman')->insertGetId([
            'kode' => 'JAT-UJI',
            'nama' => 'Jenis Ancaman Pengujian',
            'deskripsi' => null,
        ]);

        $ancamanId = DB::table('ancaman')->insertGetId([
            'jenis_ancaman_id' => $jenisAncamanId,
            'kode' => 'ANC-UJI',
            'nama' => 'Ancaman Pengujian',
            'deskripsi' => 'Ancaman untuk pengujian.',
        ]);

        $jenisKerentananId = DB::table('jenis_kerentanan')->insertGetId([
            'kode' => 'JK-UJI',
            'nama' => 'Jenis Kerentanan Pengujian',
            'deskripsi' => null,
        ]);

        $kerentananId = DB::table('kerentanan')->insertGetId([
            'jenis_kerentanan_id' => $jenisKerentananId,
            'kode' => 'KER-UJI',
            'nama' => 'Kerentanan Pengujian',
            'deskripsi' => 'Kerentanan untuk pengujian.',
        ]);

        return [
            'unit_id' => (int) $unitId,
            'user_id' => (int) $userId,
            'pegawai_id' => (int) $pegawaiId,
            'versi_metode_id' => (int) $versiMetodeId,
            'sumber_ancaman_id' => (int) $sumberAncamanId,
            'ancaman_id' => (int) $ancamanId,
            'kerentanan_id' => (int) $kerentananId,
        ];
    }

    /**
     * @param array{
     *     unit_id: int,
     *     user_id: int,
     *     versi_metode_id: int
     * } $references
     */
    private function createAssessment(array $references, string $suffix): int
    {
        $seriId = DB::table('seri_asesmen')->insertGetId([
            'kode' => 'SERI-'.$suffix,
            'nama' => 'Seri Pengujian '.$suffix,
        ]);

        $periodId = DB::table('periode_penilaian')->insertGetId([
            'unit_id' => $references['unit_id'],
            'dibuat_oleh' => $references['user_id'],
            'nama_periode' => 'Periode Pengujian '.$suffix,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'status' => 'DRAFT',
            'dibuat_pada' => '2026-01-01 00:00:00',
            'diperbarui_pada' => '2026-01-01 00:00:00',
        ]);

        return (int) DB::table('asesmen_risiko')->insertGetId([
            'seri_asesmen_id' => $seriId,
            'versi_sebelumnya_id' => null,
            'periode_id' => $periodId,
            'unit_id' => $references['unit_id'],
            'versi_metode_id' => $references['versi_metode_id'],
            'dibuat_oleh' => $references['user_id'],
            'tujuan' => 'Pengujian konsistensi asesmen.',
            'ruang_lingkup' => 'Ruang lingkup pengujian.',
            'status' => 'DRAFT',
            'nomor_versi' => 1,
            'dibuat_pada' => '2026-01-01 00:00:00',
            'dibekukan_pada' => null,
        ]);
    }

    /**
     * @param array{
     *     user_id: int,
     *     pegawai_id: int,
     *     sumber_ancaman_id: int,
     *     ancaman_id: int,
     *     kerentanan_id: int
     * } $references
     */
    private function createRisk(array $references, int $assessmentId): int
    {
        return (int) DB::table('risiko')->insertGetId([
            'asesmen_id' => $assessmentId,
            'ancaman_id' => $references['ancaman_id'],
            'sumber_ancaman_id' => $references['sumber_ancaman_id'],
            'kerentanan_id' => $references['kerentanan_id'],
            'pemilik_risiko_id' => $references['pegawai_id'],
            'dibuat_oleh' => $references['user_id'],
            'kode' => 'RISIKO-'.$assessmentId,
            'skenario' => 'Skenario pengujian.',
            'uraian_kerentanan' => 'Uraian kerentanan pengujian.',
            'uraian_dampak' => 'Uraian dampak pengujian.',
            'arah_dampak' => true,
            'dibuat_pada' => '2026-01-01 00:00:00',
        ]);
    }

    private function createPlan(
        int $assessmentId,
        int $pegawaiId,
        string $category = 'HINDARI',
        ?string $status = null,
    ): int {
        $attributes = [
            'asesmen_id' => $assessmentId,
            'pic_id' => $pegawaiId,
            'kode' => 'RENCANA-'.$assessmentId.'-'.$category,
            'uraian' => 'Rencana pengujian.',
            'target_selesai' => '2026-12-31',
            'kategori_penanganan' => $category,
            'dibuat_pada' => '2026-01-01 00:00:00',
        ];

        if ($status !== null) {
            $attributes['status'] = $status;
        }

        return (int) DB::table('rencana_penanganan')->insertGetId(
            $attributes
        );
    }
}
