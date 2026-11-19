<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'versi_metode_id',
    'kode',
    'nama',
    'urutan',
])]
class TingkatRisiko extends Model
{
    protected $table = 'tingkat_risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'versi_metode_id' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function versiMetode(): BelongsTo
    {
        return $this->belongsTo(VersiMetodeRisiko::class, 'versi_metode_id');
    }

    public function matriksRisiko(): HasMany
    {
        return $this->hasMany(MatriksRisiko::class, 'tingkat_risiko_id');
    }
}
