<?php

namespace Tests\Unit;

use App\Models\BuktiKondisiAset;
use App\Models\Dokumen;
use App\Models\Risiko;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class BuktiKondisiAsetTest extends TestCase
{
    public function test_model_uses_correct_table_without_timestamps(): void
    {
        $model = new BuktiKondisiAset;

        $this->assertSame('bukti_kondisi_aset', $model->getTable());
        $this->assertFalse($model->timestamps);
    }

    public function test_model_has_correct_fillable_attributes(): void
    {
        $model = new BuktiKondisiAset;

        $this->assertSame([
            'risiko_id',
            'dokumen_id',
            'keterangan',
            'dibuat_pada',
        ], $model->getFillable());
    }

    public function test_model_casts_attributes_correctly(): void
    {
        $model = new BuktiKondisiAset;
        $casts = $model->getCasts();

        $this->assertSame('int', $casts['id']);
        $this->assertSame('integer', $casts['risiko_id']);
        $this->assertSame('integer', $casts['dokumen_id']);
        $this->assertSame('datetime', $casts['dibuat_pada']);
    }

    public function test_model_belongs_to_risiko(): void
    {
        $relation = (new BuktiKondisiAset)->risiko();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('risiko_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
    }

    public function test_model_belongs_to_dokumen(): void
    {
        $relation = (new BuktiKondisiAset)->dokumen();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('dokumen_id', $relation->getForeignKeyName());
        $this->assertSame('id', $relation->getOwnerKeyName());
    }

    public function test_risiko_has_many_bukti_kondisi_aset(): void
    {
        $relation = (new Risiko)->buktiKondisiAset();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('risiko_id', $relation->getForeignKeyName());
    }

    public function test_dokumen_has_many_bukti_kondisi_aset(): void
    {
        $relation = (new Dokumen)->buktiKondisiAset();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('dokumen_id', $relation->getForeignKeyName());
    }
}
