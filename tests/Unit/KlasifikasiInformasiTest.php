<?php

namespace Tests\Unit;

use App\Models\KlasifikasiInformasi;
use PHPUnit\Framework\TestCase;

class KlasifikasiInformasiTest extends TestCase
{
    public function test_klasifikasi_informasi_uses_correct_table(): void
    {
        $this->assertSame(
            'klasifikasi_informasi',
            (new KlasifikasiInformasi)->getTable()
        );
    }

    public function test_klasifikasi_informasi_has_correct_fillable_attributes(): void
    {
        $this->assertSame(
            ['kode', 'nama', 'deskripsi'],
            (new KlasifikasiInformasi)->getFillable()
        );
    }

    public function test_klasifikasi_informasi_does_not_use_timestamps(): void
    {
        $this->assertFalse((new KlasifikasiInformasi)->usesTimestamps());
    }
}
