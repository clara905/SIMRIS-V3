<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pegawai_id');
    }

    public function roleScopes(): HasMany
    {
        return $this->hasMany(UserRoleScope::class, 'user_id');
    }

    public function pengajuanAkun(): HasMany
    {
        return $this->hasMany(PengajuanAkun::class, 'user_id');
    }

    public function logAudit(): HasMany
    {
        return $this->hasMany(LogAudit::class, 'user_id');
    }

    public function periodePenilaianDibuat(): HasMany
    {
        return $this->hasMany(PeriodePenilaian::class, 'dibuat_oleh');
    }

    public function penilaianRisikoDibuat(): HasMany
    {
        return $this->hasMany(PenilaianRisiko::class, 'dinilai_oleh');
    }

    public function asesmenRisikoDibuat(): HasMany
    {
        return $this->hasMany(AsesmenRisiko::class, 'dibuat_oleh');
    }
}
