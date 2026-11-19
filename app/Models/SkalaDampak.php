<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'versi_metode_id',
    'nilai',
    'nama',
    'deskripsi',
])]
class SkalaDampak extends Model
{
    protected $table = 'skala_dampak';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'versi_metode_id' => 'integer',
            'nilai' => 'integer',
        ];
    }

    public function versiMetode(): BelongsTo
    {
        return $this->belongsTo(VersiMetodeRisiko::class, 'versi_metode_id');
    }

    public function matriksRisiko(): HasMany
    {
        return $this->hasMany(MatriksRisiko::class, 'dampak_id');
    }
}
