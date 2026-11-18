<?php

namespace Tests\Unit;

use App\Models\PenugasanAset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class PenugasanAsetTest extends TestCase
{
    public function test_penugasan_aset_uses_correct_table(): void
    {
        $this->assertSame(
            'penugasan_aset',
            (new PenugasanAset)->getTable()
        );
    }

    public function test_penugasan_aset_does_not_use_timestamps(): void
    {
        $this->assertFalse((new PenugasanAset)->usesTimestamps());
    }

    public function test_penugasan_aset_has_correct_fillable_attributes(): void
    {
        $this->assertSame([
            'aset_idx',
            'pegawai_id',
            'jenis_tanggung_jawab',
            'mulai',
            'selesai',
        ], (new PenugasanAset)->getFillable());
    }

    public function test_penugasan_aset_casts_attributes_correctly(): void
    {
        $casts = (new PenugasanAset)->getCasts();

        $this->assertSame('integer', $casts['aset_idx']);
        $this->assertSame('integer', $casts['pegawai_id']);
        $this->assertSame('date', $casts['mulai']);
        $this->assertSame('date', $casts['selesai']);
    }

    public function test_penugasan_aset_belongs_to_aset_using_idx(): void
    {
        $relation = (new PenugasanAset)->aset();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('aset_idx', $relation->getForeignKeyName());
        $this->assertSame('idx', $relation->getOwnerKeyName());
    }

    public function test_penugasan_aset_belongs_to_pegawai(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new PenugasanAset)->pegawai()
        );
    }
}
