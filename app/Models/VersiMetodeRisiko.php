<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode_versi',
    'nama',
    'deskripsi',
    'dibekukan',
    'dibekukan_pada',
])]
class VersiMetodeRisiko extends Model
{
    protected $table = 'versi_metode_risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dibekukan' => 'boolean',
            'dibekukan_pada' => 'datetime',
        ];
    }

    public function skalaKemungkinan(): HasMany
    {
        return $this->hasMany(SkalaKemungkinan::class, 'versi_metode_id');
    }

    public function skalaDampak(): HasMany
    {
        return $this->hasMany(SkalaDampak::class, 'versi_metode_id');
    }

    public function tingkatRisiko(): HasMany
    {
        return $this->hasMany(TingkatRisiko::class, 'versi_metode_id');
    }

    public function matriksRisiko(): HasMany
    {
        return $this->hasMany(MatriksRisiko::class, 'versi_metode_id');
    }
}
