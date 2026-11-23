<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'nama',
])]
class SumberAncaman extends Model
{
    protected $table = 'sumber_ancaman';

    public $timestamps = false;

    public function risiko(): HasMany
    {
        return $this->hasMany(Risiko::class, 'sumber_ancaman_id');
    }
}
