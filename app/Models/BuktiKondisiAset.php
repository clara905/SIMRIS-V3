<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuktiKondisiAset extends Model
{
    protected $table = 'bukti_kondisi_aset';

    public $timestamps = false;

    protected $fillable = [
        'risiko_id',
        'dokumen_id',
        'keterangan',
        'dibuat_pada',
    ];

    protected function casts(): array
    {
        return [
            'risiko_id' => 'integer',
            'dokumen_id' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function risiko(): BelongsTo
    {
        return $this->belongsTo(Risiko::class, 'risiko_id');
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class, 'dokumen_id');
    }
}
