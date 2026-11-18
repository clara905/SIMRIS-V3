<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'status',
    'catatan',
    'ditinjau_oleh',
    'diajukan_pada',
    'ditinjau_pada',
])]
class PengajuanAkun extends Model
{
    public const STATUS_MENUNGGU = 'MENUNGGU';

    public const STATUS_DISETUJUI = 'DISETUJUI';

    public const STATUS_DITOLAK = 'DITOLAK';

    protected $table = 'pengajuan_akun';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'ditinjau_oleh' => 'integer',
            'diajukan_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }
}
