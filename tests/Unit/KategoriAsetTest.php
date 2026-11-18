<?php

namespace Tests\Unit;

use App\Models\KategoriAset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class KategoriAsetTest extends TestCase
{
    public function test_kategori_aset_uses_correct_table(): void
    {
        $this->assertSame('kategori_aset', (new KategoriAset)->getTable());
    }

    public function test_kategori_aset_has_correct_fillable_attributes(): void
    {
        $this->assertSame(
            ['parent_id', 'kode', 'nama'],
            (new KategoriAset)->getFillable()
        );
    }

    public function test_kategori_aset_does_not_use_timestamps(): void
    {
        $this->assertFalse((new KategoriAset)->usesTimestamps());
    }

    public function test_parent_id_is_cast_to_integer(): void
    {
        $this->assertSame(
            'integer',
            (new KategoriAset)->getCasts()['parent_id']
        );
    }

    public function test_kategori_aset_has_parent_relation(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new KategoriAset)->parentKategori()
        );
    }

    public function test_kategori_aset_has_children_relation(): void
    {
        $this->assertInstanceOf(
            HasMany::class,
            (new KategoriAset)->children()
        );
    }
}
