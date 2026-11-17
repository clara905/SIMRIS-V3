<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pegawai_id',
    'unit_id',
    'mulai',
    'selesai',
])]
class PenempatanPegawai extends Model
{
    protected $table = 'penempatan_pegawai';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pegawai_id' => 'integer',
            'unit_id' => 'integer',
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_id');
    }
}
