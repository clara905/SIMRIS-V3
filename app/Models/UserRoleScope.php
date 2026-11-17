<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'role_id',
    'unit_id',
    'mulai',
    'selesai',
])]
class UserRoleScope extends Model
{
    protected $table = 'user_role_scope';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'role_id' => 'integer',
            'unit_id' => 'integer',
            'mulai' => 'datetime',
            'selesai' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_id');
    }
}
