<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kode',
    'nama',
])]
class SumberAset extends Model
{
    protected $table = 'sumber_aset';

    public $timestamps = false;
}
