<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'nama',
])]
class SeriAsesmen extends Model
{
    protected $table = 'seri_asesmen';

    public $timestamps = false;

    public function asesmen(): HasMany
    {
        return $this->hasMany(AsesmenRisiko::class, 'seri_asesmen_id');
    }
}
