<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException(
                "Driver database [{$driver}] belum didukung migration ini."
            );
        }

        if (DB::table('rencana_penanganan')->exists()) {
            throw new RuntimeException(
                'Migration dibatalkan: kategori rencana lama harus dipetakan secara eksplisit sebelum kolom wajib ditambahkan.'
            );
        }

        Schema::table('rencana_penanganan', function (Blueprint $table) {
            $table->string('kategori_penanganan', 20)
                ->after('target_selesai');
            $table->string('status', 50)
                ->default('SEDANG_DILAKSANAKAN')
                ->change();
        });

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE rencana_penanganan
                ADD CONSTRAINT chk_rencana_penanganan_kategori
                CHECK (kategori_penanganan IN (
                    'HINDARI',
                    'MITIGASI',
                    'ALIH_BAGI',
                    'TERIMA'
                ))
            ");

            DB::statement("
                ALTER TABLE rencana_penanganan
                ADD CONSTRAINT chk_rencana_penanganan_status
                CHECK (status IN (
                    'SEDANG_DILAKSANAKAN',
                    'SELESAI'
                ))
            ");

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER trg_rencana_penanganan_before_insert
                BEFORE INSERT ON rencana_penanganan
                FOR EACH ROW
                BEGIN
                    IF NEW.status <> 'SEDANG_DILAKSANAKAN' THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rencana baru harus berstatus SEDANG_DILAKSANAKAN.';
                    END IF;
                END
            SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER trg_rencana_penanganan_before_update
                BEFORE UPDATE ON rencana_penanganan
                FOR EACH ROW
                BEGIN
                    IF OLD.status = 'SELESAI'
                        AND NEW.status <> OLD.status THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Rencana SELESAI tidak dapat dibuka kembali.';
                    END IF;

                    IF OLD.status = 'SELESAI'
                        AND NEW.kategori_penanganan <> OLD.kategori_penanganan THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Kategori rencana SELESAI tidak dapat diubah.';
                    END IF;
                END
            SQL);

            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_rencana_penanganan_before_insert
            BEFORE INSERT ON rencana_penanganan
            FOR EACH ROW
            WHEN NEW.kategori_penanganan NOT IN (
                'HINDARI',
                'MITIGASI',
                'ALIH_BAGI',
                'TERIMA'
            ) OR NEW.status <> 'SEDANG_DILAKSANAKAN'
            BEGIN
                SELECT RAISE(ABORT, 'Kategori atau status awal rencana tidak valid.');
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_rencana_penanganan_before_update
            BEFORE UPDATE ON rencana_penanganan
            FOR EACH ROW
            WHEN NEW.kategori_penanganan NOT IN (
                'HINDARI',
                'MITIGASI',
                'ALIH_BAGI',
                'TERIMA'
            ) OR NEW.status NOT IN (
                'SEDANG_DILAKSANAKAN',
                'SELESAI'
            ) OR (
                OLD.status = 'SELESAI'
                AND (
                    NEW.status <> OLD.status
                    OR NEW.kategori_penanganan <> OLD.kategori_penanganan
                )
            )
            BEGIN
                SELECT RAISE(ABORT, 'Transisi atau perubahan kategori rencana tidak valid.');
            END
        SQL);
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (DB::table('rencana_penanganan')->exists()) {
            throw new RuntimeException(
                'Rollback dibatalkan agar nilai kategori rencana yang sudah tersimpan tidak hilang.'
            );
        }

        DB::unprepared(
            'DROP TRIGGER IF EXISTS trg_rencana_penanganan_before_insert'
        );
        DB::unprepared(
            'DROP TRIGGER IF EXISTS trg_rencana_penanganan_before_update'
        );

        if ($driver === 'mysql') {
            DB::statement('
                ALTER TABLE rencana_penanganan
                DROP CHECK chk_rencana_penanganan_kategori
            ');
            DB::statement('
                ALTER TABLE rencana_penanganan
                DROP CHECK chk_rencana_penanganan_status
            ');
        }

        Schema::table('rencana_penanganan', function (Blueprint $table) {
            $table->string('status', 50)
                ->default(null)
                ->change();
            $table->dropColumn('kategori_penanganan');
        });
    }
};
