<?php

namespace Database\Seeders;

use App\Models\SumberAncaman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SumberAncamanSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $sources = [
                'S' => 'Sengaja',
                'TS' => 'Tidak Sengaja',
                'L' => 'Lingkungan',
            ];

            foreach ($sources as $kode => $nama) {
                SumberAncaman::firstOrCreate(
                    ['kode' => $kode],
                    ['nama' => $nama]
                );
            }
        });
    }
}
