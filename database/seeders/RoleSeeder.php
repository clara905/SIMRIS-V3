<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'kode' => 'SUPER_ADMIN',
                'nama' => 'Super Admin',
            ],
            [
                'kode' => 'ADMIN_SATKER',
                'nama' => 'Admin Satker',
            ],
            [
                'kode' => 'KASATKER',
                'nama' => 'Kepala Satuan Kerja',
            ],
            [
                'kode' => 'KABID',
                'nama' => 'Kepala Bidang',
            ],
            [
                'kode' => 'KASUB',
                'nama' => 'Kepala Subbidang',
            ],
            [
                'kode' => 'STAFF',
                'nama' => 'Staff',
            ],
        ];

        DB::transaction(function () use ($roles): void {
            foreach ($roles as $role) {
                Role::firstOrCreate(
                    ['kode' => $role['kode']],
                    ['nama' => $role['nama']]
                );
            }
        });
    }
}
