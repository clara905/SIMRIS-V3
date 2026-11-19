<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'versi_metode_id',
    'kemungkinan_id',
    'dampak_id',
    'tingkat_risiko_id',
    'skor',
    'rekomendasi_keputusan',
])]
class MatriksRisiko extends Model
{
    protected $table = 'matriks_risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'versi_metode_id' => 'integer',
            'kemungkinan_id' => 'integer',
            'dampak_id' => 'integer',
            'tingkat_risiko_id' => 'integer',
            'skor' => 'integer',
        ];
    }

    public function versiMetode(): BelongsTo
    {
        return $this->belongsTo(VersiMetodeRisiko::class, 'versi_metode_id');
    }

    public function kemungkinan(): BelongsTo
    {
        return $this->belongsTo(SkalaKemungkinan::class, 'kemungkinan_id');
    }

    public function dampak(): BelongsTo
    {
        return $this->belongsTo(SkalaDampak::class, 'dampak_id');
    }

    public function tingkatRisiko(): BelongsTo
    {
        return $this->belongsTo(TingkatRisiko::class, 'tingkat_risiko_id');
    }
}
