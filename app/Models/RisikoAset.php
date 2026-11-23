<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
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
])]
class RisikoAset extends Model
{
    protected $table = 'risiko_aset';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'risiko_id' => 'integer',
            'aset_idx' => 'integer',
            'direkam_pada' => 'datetime',
        ];
    }

    public function risiko(): BelongsTo
    {
        return $this->belongsTo(Risiko::class, 'risiko_id');
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_idx', 'idx');
    }
}
