<?php

namespace Tests\Unit;

use App\Models\Pegawai;
use Tests\TestCase;

class PegawaiTest extends TestCase
{
    public function test_pegawai_uses_correct_database_table(): void
    {
        $pegawai = new Pegawai;

        $this->assertSame('pegawai', $pegawai->getTable());
        $this->assertFalse($pegawai->usesTimestamps());
    }

    public function test_pegawai_has_correct_fillable_attributes(): void
    {
        $pegawai = new Pegawai;

        $this->assertSame([
            'nip',
            'nama',
            'email_kontak',
            'jenis_kepegawaian',
            'aktif',
        ], $pegawai->getFillable());
    }

    public function test_pegawai_casts_aktif_to_boolean(): void
    {
        $pegawai = new Pegawai([
            'aktif' => 1,
        ]);

        $this->assertTrue($pegawai->aktif);
    }

    public function test_pegawai_accepts_all_employment_types(): void
    {
        $jenisKepegawaian = [
            'PNS',
            'PPPK',
            'NON_PNS',
            'TNI',
        ];

        foreach ($jenisKepegawaian as $jenis) {
            $pegawai = new Pegawai([
                'jenis_kepegawaian' => $jenis,
            ]);

            $this->assertSame($jenis, $pegawai->jenis_kepegawaian);
        }
    }
}
