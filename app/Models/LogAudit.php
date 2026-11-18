<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'user_id',
    'pelaku_nama',
    'pelaku_username',
    'aksi',
    'entitas',
    'entitas_id',
    'data_lama',
    'data_baru',
    'ip_address',
    'koreksi_dari_id',
    'catatan',
    'waktu',
])]
class LogAudit extends Model
{
    protected $table = 'log_audit';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'entitas_id' => 'integer',
            'koreksi_dari_id' => 'integer',
            'data_lama' => 'array',
            'data_baru' => 'array',
            'waktu' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $logAudit): void {
            throw new LogicException(
                'Log audit bersifat immutable dan tidak dapat diubah.'
            );
        });

        static::deleting(function (self $logAudit): void {
            throw new LogicException(
                'Log audit bersifat immutable dan tidak dapat dihapus.'
            );
        });
    }

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function koreksiDari(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'koreksi_dari_id'
        );
    }

    public function koreksi(): HasMany
    {
        return $this->hasMany(
            self::class,
            'koreksi_dari_id'
        );
    }
}
