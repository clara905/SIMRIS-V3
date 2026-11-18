<?php

namespace Tests\Unit;

use App\Models\Aset;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class AsetTest extends TestCase
{
    public function test_aset_uses_correct_table(): void
    {
        $this->assertSame('aset', (new Aset)->getTable());
    }

    public function test_aset_uses_idx_as_primary_key(): void
    {
        $this->assertSame('idx', (new Aset)->getKeyName());
        $this->assertTrue((new Aset)->getIncrementing());
    }

    public function test_aset_uses_custom_timestamp_columns(): void
    {
        $aset = new Aset;

        $this->assertTrue($aset->usesTimestamps());
        $this->assertSame('dibuat_pada', $aset->getCreatedAtColumn());
        $this->assertSame('diperbarui_pada', $aset->getUpdatedAtColumn());
    }

    public function test_aset_has_correct_fillable_attributes(): void
    {
        $this->assertSame([
            'sumber_id',
            'external_id',
            'unit_pemilik_id',
            'kategori_id',
            'klasifikasi_informasi_id',
            'kode_aset',
            'nama',
            'deskripsi',
            'merk',
            'nomor_seri',
            'jumlah',
            'kondisi',
            'lokasi',
            'tanggal_pengadaan',
            'status',
            'sumber_perolehan',
            'last_synced_at',
        ], (new Aset)->getFillable());
    }

    public function test_aset_casts_attributes_correctly(): void
    {
        $casts = (new Aset)->getCasts();

        $this->assertSame('integer', $casts['idx']);
        $this->assertSame('integer', $casts['jumlah']);
        $this->assertSame('date', $casts['tanggal_pengadaan']);
        $this->assertSame('datetime', $casts['last_synced_at']);
    }

    public function test_aset_belongs_to_sumber_aset(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Aset)->sumberAset()
        );
    }

    public function test_aset_belongs_to_unit_pemilik(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Aset)->unitPemilik()
        );
    }

    public function test_aset_belongs_to_kategori(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Aset)->kategori()
        );
    }

    public function test_aset_belongs_to_klasifikasi_informasi(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Aset)->klasifikasiInformasi()
        );
    }

    public function test_aset_has_many_penugasan(): void
    {
        $relation = (new Aset)->penugasan();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('aset_idx', $relation->getForeignKeyName());
        $this->assertSame('idx', $relation->getLocalKeyName());
    }
}
