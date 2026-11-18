<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'aset_idx',
    'pegawai_id',
    'jenis_tanggung_jawab',
    'mulai',
    'selesai',
])]
class PenugasanAset extends Model
{
    protected $table = 'penugasan_aset';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'aset_idx' => 'integer',
            'pegawai_id' => 'integer',
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function aset(): BelongsTo
    {
        return $this->belongsTo(Aset::class, 'aset_idx', 'idx');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }
}
