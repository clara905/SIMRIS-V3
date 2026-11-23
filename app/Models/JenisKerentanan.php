<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'nama',
])]
class JenisKerentanan extends Model
{
    protected $table = 'jenis_kerentanan';

    public $timestamps = false;

    public function kerentanan(): HasMany
    {
        return $this->hasMany(Kerentanan::class, 'jenis_kerentanan_id');
    }
}
