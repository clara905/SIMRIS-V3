<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
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
])]
class Aset extends Model
{
    protected $table = 'aset';

    protected $primaryKey = 'idx';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    protected function casts(): array
    {
        return [
            'idx' => 'integer',
            'sumber_id' => 'integer',
            'unit_pemilik_id' => 'integer',
            'kategori_id' => 'integer',
            'klasifikasi_informasi_id' => 'integer',
            'jumlah' => 'integer',
            'tanggal_pengadaan' => 'date',
            'last_synced_at' => 'datetime',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function sumberAset(): BelongsTo
    {
        return $this->belongsTo(SumberAset::class, 'sumber_id');
    }

    public function unitPemilik(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_pemilik_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriAset::class, 'kategori_id');
    }

    public function klasifikasiInformasi(): BelongsTo
    {
        return $this->belongsTo(
            KlasifikasiInformasi::class,
            'klasifikasi_informasi_id'
        );
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanAset::class, 'aset_idx', 'idx');
    }
}
