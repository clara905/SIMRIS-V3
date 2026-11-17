<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode',
    'nama',
])]
class Role extends Model
{
    protected $table = 'roles';

    public $timestamps = false;

    public function userRoleScopes(): HasMany
    {
        return $this->hasMany(UserRoleScope::class, 'role_id');
    }
}
