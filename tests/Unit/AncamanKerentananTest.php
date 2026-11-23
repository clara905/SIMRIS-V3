<?php

namespace Tests\Unit;

use App\Models\Ancaman;
use App\Models\JenisAncaman;
use App\Models\JenisKerentanan;
use App\Models\Kerentanan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class AncamanKerentananTest extends TestCase
{
    public function test_models_use_correct_tables_without_timestamps(): void
    {
        $this->assertSame('jenis_ancaman', (new JenisAncaman)->getTable());
        $this->assertSame('ancaman', (new Ancaman)->getTable());
        $this->assertSame('jenis_kerentanan', (new JenisKerentanan)->getTable());
        $this->assertSame('kerentanan', (new Kerentanan)->getTable());

        $this->assertFalse((new JenisAncaman)->timestamps);
        $this->assertFalse((new Ancaman)->timestamps);
        $this->assertFalse((new JenisKerentanan)->timestamps);
        $this->assertFalse((new Kerentanan)->timestamps);
    }

    public function test_jenis_ancaman_has_correct_attributes(): void
    {
        $model = new JenisAncaman;

        $this->assertSame(['kode', 'nama'], $model->getFillable());
    }

    public function test_ancaman_has_correct_attributes_and_casts(): void
    {
        $model = new Ancaman;

        $this->assertSame([
            'jenis_ancaman_id',
            'kode',
            'nama',
            'deskripsi',
        ], $model->getFillable());

        $this->assertSame(
            'integer',
            $model->getCasts()['jenis_ancaman_id']
        );
    }

    public function test_jenis_kerentanan_has_correct_attributes(): void
    {
        $model = new JenisKerentanan;

        $this->assertSame(['kode', 'nama'], $model->getFillable());
    }

    public function test_kerentanan_has_correct_attributes_and_casts(): void
    {
        $model = new Kerentanan;

        $this->assertSame([
            'jenis_kerentanan_id',
            'kode',
            'nama',
            'deskripsi',
        ], $model->getFillable());

        $this->assertSame(
            'integer',
            $model->getCasts()['jenis_kerentanan_id']
        );
    }

    public function test_jenis_ancaman_has_many_ancaman(): void
    {
        $model = new JenisAncaman;

        $this->assertInstanceOf(HasMany::class, $model->ancaman());
    }

    public function test_ancaman_belongs_to_jenis_ancaman(): void
    {
        $model = new Ancaman;

        $this->assertInstanceOf(BelongsTo::class, $model->jenisAncaman());
    }

    public function test_jenis_kerentanan_has_many_kerentanan(): void
    {
        $model = new JenisKerentanan;

        $this->assertInstanceOf(HasMany::class, $model->kerentanan());
    }

    public function test_kerentanan_belongs_to_jenis_kerentanan(): void
    {
        $model = new Kerentanan;

        $this->assertInstanceOf(BelongsTo::class, $model->jenisKerentanan());
    }
}
