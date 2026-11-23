<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'nama',
])]
class JenisAncaman extends Model
{
    protected $table = 'jenis_ancaman';

    public $timestamps = false;

    public function ancaman(): HasMany
    {
        return $this->hasMany(Ancaman::class, 'jenis_ancaman_id');
    }
}
