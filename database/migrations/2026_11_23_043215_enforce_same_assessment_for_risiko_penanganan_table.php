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

        $mismatchedPairs = DB::table('risiko_penanganan as rp')
            ->join('risiko as r', 'r.id', '=', 'rp.risiko_id')
            ->join(
                'rencana_penanganan as rph',
                'rph.id',
                '=',
                'rp.rencana_penanganan_id'
            )
            ->whereColumn('r.asesmen_id', '<>', 'rph.asesmen_id')
            ->count();

        if ($mismatchedPairs > 0) {
            throw new RuntimeException(
                'Migration dibatalkan: terdapat risiko dan rencana penanganan dari asesmen yang berbeda.'
            );
        }

        Schema::table('risiko', function (Blueprint $table) {
            $table->unique(
                ['asesmen_id', 'id'],
                'uq_risiko_asesmen_id'
            );
        });

        Schema::table('rencana_penanganan', function (Blueprint $table) {
            $table->unique(
                ['asesmen_id', 'id'],
                'uq_rencana_penanganan_asesmen_id'
            );
        });

        Schema::table('risiko_penanganan', function (Blueprint $table) {
            $table->unsignedBigInteger('asesmen_id')
                ->nullable()
                ->after('risiko_id');
        });

        if ($driver === 'mysql') {
            DB::statement('
                UPDATE risiko_penanganan AS rp
                INNER JOIN rencana_penanganan AS rph
                    ON rph.id = rp.rencana_penanganan_id
                SET rp.asesmen_id = rph.asesmen_id
            ');
        } else {
            DB::statement('
                UPDATE risiko_penanganan
                SET asesmen_id = (
                    SELECT rph.asesmen_id
                    FROM rencana_penanganan AS rph
                    WHERE rph.id = risiko_penanganan.rencana_penanganan_id
                )
            ');
        }

        if (DB::table('risiko_penanganan')->whereNull('asesmen_id')->exists()) {
            throw new RuntimeException(
                'Migration dibatalkan: asesmen_id tidak dapat ditentukan untuk seluruh relasi risiko-rencana.'
            );
        }

        Schema::table('risiko_penanganan', function (Blueprint $table) use ($driver) {
            $table->unsignedBigInteger('asesmen_id')->nullable(false)->change();

            if ($driver === 'mysql') {
                $table->dropForeign('risiko_penanganan_risiko_id_foreign');
                $table->dropForeign(
                    'risiko_penanganan_rencana_penanganan_id_foreign'
                );
            }

            $table->index(
                ['asesmen_id', 'risiko_id'],
                'idx_risiko_penanganan_asesmen_risiko'
            );
            $table->index(
                ['asesmen_id', 'rencana_penanganan_id'],
                'idx_risiko_penanganan_asesmen_rencana'
            );
            $table->foreign(
                ['asesmen_id', 'risiko_id'],
                'fk_risiko_penanganan_asesmen_risiko'
            )
                ->references(['asesmen_id', 'id'])
                ->on('risiko')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->foreign(
                ['asesmen_id', 'rencana_penanganan_id'],
                'fk_risiko_penanganan_asesmen_rencana'
            )
                ->references(['asesmen_id', 'id'])
                ->on('rencana_penanganan')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        if (DB::table('rencana_penanganan')->exists()) {
            throw new RuntimeException(
                'Rollback ditolak karena tabel rencana_penanganan masih memiliki data. Rollback dapat menghilangkan informasi kategori penanganan.'
            );
        }

        if (DB::getDriverName() === 'mysql') {
            Schema::table('risiko_penanganan', function (Blueprint $table) {
                $table->dropForeign(
                    'fk_risiko_penanganan_asesmen_risiko'
                );
                $table->dropForeign(
                    'fk_risiko_penanganan_asesmen_rencana'
                );
                $table->dropIndex(
                    'idx_risiko_penanganan_asesmen_risiko'
                );
                $table->dropIndex(
                    'idx_risiko_penanganan_asesmen_rencana'
                );
                $table->foreign(
                    'risiko_id',
                    'risiko_penanganan_risiko_id_foreign'
                )
                    ->references('id')
                    ->on('risiko')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->foreign(
                    'rencana_penanganan_id',
                    'risiko_penanganan_rencana_penanganan_id_foreign'
                )
                    ->references('id')
                    ->on('rencana_penanganan')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->dropColumn('asesmen_id');
            });
        } else {
            Schema::create('risiko_penanganan_rollback', function (Blueprint $table) {
                $table->unsignedBigInteger('risiko_id');
                $table->unsignedBigInteger('rencana_penanganan_id');
                $table->primary([
                    'risiko_id',
                    'rencana_penanganan_id',
                ]);
                $table->foreign(
                    'risiko_id',
                    'risiko_penanganan_risiko_id_foreign'
                )
                    ->references('id')
                    ->on('risiko')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->foreign(
                    'rencana_penanganan_id',
                    'risiko_penanganan_rencana_penanganan_id_foreign'
                )
                    ->references('id')
                    ->on('rencana_penanganan')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });

            DB::statement('
                INSERT INTO risiko_penanganan_rollback (
                    risiko_id,
                    rencana_penanganan_id
                )
                SELECT risiko_id, rencana_penanganan_id
                FROM risiko_penanganan
            ');

            Schema::drop('risiko_penanganan');
            Schema::rename(
                'risiko_penanganan_rollback',
                'risiko_penanganan'
            );
        }

        Schema::table('rencana_penanganan', function (Blueprint $table) {
            $table->dropUnique(
                'uq_rencana_penanganan_asesmen_id'
            );
        });

        Schema::table('risiko', function (Blueprint $table) {
            $table->dropUnique('uq_risiko_asesmen_id');
        });
    }
};
