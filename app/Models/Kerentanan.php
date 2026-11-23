<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'jenis_kerentanan_id',
    'kode',
    'nama',
    'deskripsi',
])]
class Kerentanan extends Model
{
    protected $table = 'kerentanan';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'jenis_kerentanan_id' => 'integer',
        ];
    }

    public function jenisKerentanan(): BelongsTo
    {
        return $this->belongsTo(
            JenisKerentanan::class,
            'jenis_kerentanan_id'
        );
    }
}
