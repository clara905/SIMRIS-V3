<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kode',
    'nama',
    'deskripsi',
])]
class KlasifikasiInformasi extends Model
{
    protected $table = 'klasifikasi_informasi';

    public $timestamps = false;
}
