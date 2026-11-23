<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'risiko_id',
    'matriks_id',
    'dinilai_oleh',
    'jenis_penilaian',
    'alasan_kemungkinan',
    'alasan_dampak',
    'kesimpulan',
    'dinilai_pada',
])]
class PenilaianRisiko extends Model
{
    protected $table = 'penilaian_risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'risiko_id' => 'integer',
            'matriks_id' => 'integer',
            'dinilai_oleh' => 'integer',
            'dinilai_pada' => 'datetime',
        ];
    }

    public function risiko(): BelongsTo
    {
        return $this->belongsTo(Risiko::class, 'risiko_id');
    }

    public function matriks(): BelongsTo
    {
        return $this->belongsTo(MatriksRisiko::class, 'matriks_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinilai_oleh');
    }
}
