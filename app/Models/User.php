<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'pegawai_id',
    'username',
    'email',
    'firebase_uid',
    'aktif',
])]
#[Hidden(['firebase_uid'])]
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Nama tabel database.
     */
    protected $table = 'users';

    /**
     * Tabel users tidak menggunakan timestamps Laravel.
     */
    public $timestamps = false;

    /**
     * Konversi tipe atribut.
     */
    protected function casts(): array
    {
        return [
            'pegawai_id' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}
