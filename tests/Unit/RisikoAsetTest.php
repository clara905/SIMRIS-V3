<?php

namespace Tests\Unit;

use App\Models\Aset;
use App\Models\Risiko;
use App\Models\RisikoAset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RisikoAsetTest extends TestCase
{
    #[Test]
    public function risiko_aset_uses_correct_table_without_timestamps(): void
    {
        $model = new RisikoAset;

        $this->assertSame('risiko_aset', $model->getTable());
        $this->assertFalse($model->usesTimestamps());
    }

    #[Test]
    public function risiko_aset_has_expected_fillable_attributes(): void
    {
        $this->assertSame([
            'risiko_id',
            'aset_idx',
            'kode_aset_snapshot',
            'nama_aset_snapshot',
            'kategori_snapshot',
            'klasifikasi_snapshot',
            'unit_pemilik_snapshot',
            'lokasi_snapshot',
            'kondisi_snapshot',
            'direkam_pada',
        ], (new RisikoAset)->getFillable());
    }

    #[Test]
    public function risiko_aset_casts_attributes_correctly(): void
    {
        $casts = (new RisikoAset)->getCasts();

        $this->assertSame('integer', $casts['risiko_id']);
        $this->assertSame('integer', $casts['aset_idx']);
        $this->assertSame('datetime', $casts['direkam_pada']);
    }

    #[Test]
    public function risiko_aset_belongs_to_risiko(): void
    {
        $relation = (new RisikoAset)->risiko();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Risiko::class, $relation->getRelated());
        $this->assertSame('risiko_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function risiko_aset_belongs_to_aset_using_idx(): void
    {
        $relation = (new RisikoAset)->aset();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Aset::class, $relation->getRelated());
        $this->assertSame('aset_idx', $relation->getForeignKeyName());
        $this->assertSame('idx', $relation->getOwnerKeyName());
    }

    #[Test]
    public function risiko_has_many_risiko_aset(): void
    {
        $relation = (new Risiko)->risikoAset();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(RisikoAset::class, $relation->getRelated());
        $this->assertSame('risiko_id', $relation->getForeignKeyName());
    }
}
