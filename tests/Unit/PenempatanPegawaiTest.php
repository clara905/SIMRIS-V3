<?php

namespace Tests\Unit;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class PenempatanPegawaiTest extends TestCase
{
    public function test_penempatan_pegawai_uses_correct_table(): void
    {
        $penempatan = new PenempatanPegawai;

        $this->assertSame('penempatan_pegawai', $penempatan->getTable());
        $this->assertFalse($penempatan->usesTimestamps());
    }

    public function test_penempatan_pegawai_has_correct_fillable_attributes(): void
    {
        $penempatan = new PenempatanPegawai;

        $this->assertSame([
            'pegawai_id',
            'unit_id',
            'mulai',
            'selesai',
        ], $penempatan->getFillable());
    }

    public function test_penempatan_pegawai_casts_attributes_correctly(): void
    {
        $penempatan = new PenempatanPegawai([
            'pegawai_id' => '1',
            'unit_id' => '2',
            'mulai' => '2026-01-01',
            'selesai' => null,
        ]);

        $this->assertSame(1, $penempatan->pegawai_id);
        $this->assertSame(2, $penempatan->unit_id);
        $this->assertSame('2026-01-01', $penempatan->mulai->toDateString());
        $this->assertNull($penempatan->selesai);
    }

    public function test_penempatan_pegawai_has_pegawai_relation(): void
    {
        $penempatan = new PenempatanPegawai;

        $this->assertInstanceOf(
            BelongsTo::class,
            $penempatan->pegawai()
        );
    }

    public function test_penempatan_pegawai_has_unit_relation(): void
    {
        $penempatan = new PenempatanPegawai;

        $this->assertInstanceOf(
            BelongsTo::class,
            $penempatan->unit()
        );
    }

    public function test_pegawai_has_penempatan_relation(): void
    {
        $pegawai = new Pegawai;

        $this->assertInstanceOf(
            HasMany::class,
            $pegawai->penempatan()
        );
    }

    public function test_unit_organisasi_has_penempatan_pegawai_relation(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertInstanceOf(
            HasMany::class,
            $unit->penempatanPegawai()
        );
    }
}
