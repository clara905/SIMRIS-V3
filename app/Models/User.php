<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected $table = 'users';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'pegawai_id' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function roleScopes(): HasMany
    {
        return $this->hasMany(UserRoleScope::class, 'user_id');
    }
}
