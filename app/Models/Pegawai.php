<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nip',
    'nama',
    'email_kontak',
    'jenis_kepegawaian',
    'aktif',
])]
class Pegawai extends Model
{
    protected $table = 'pegawai';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function penempatan(): HasMany
    {
        return $this->hasMany(PenempatanPegawai::class, 'pegawai_id');
    }
}
