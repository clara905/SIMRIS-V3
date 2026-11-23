<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'jenis_ancaman_id',
    'kode',
    'nama',
    'deskripsi',
])]
class Ancaman extends Model
{
    protected $table = 'ancaman';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'jenis_ancaman_id' => 'integer',
        ];
    }

    public function jenisAncaman(): BelongsTo
    {
        return $this->belongsTo(JenisAncaman::class, 'jenis_ancaman_id');
    }
}
