<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'asesmen_id',
    'ancaman_id',
    'sumber_ancaman_id',
    'kerentanan_id',
    'pemilik_risiko_id',
    'dibuat_oleh',
    'kode',
    'skenario',
    'uraian_kerentanan',
    'uraian_dampak',
    'arah_dampak',
    'dibuat_pada',
])]
class Risiko extends Model
{
    protected $table = 'risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'asesmen_id' => 'integer',
            'ancaman_id' => 'integer',
            'sumber_ancaman_id' => 'integer',
            'kerentanan_id' => 'integer',
            'pemilik_risiko_id' => 'integer',
            'dibuat_oleh' => 'integer',
            'arah_dampak' => 'boolean',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function asesmen(): BelongsTo
    {
        return $this->belongsTo(AsesmenRisiko::class, 'asesmen_id');
    }

    public function ancaman(): BelongsTo
    {
        return $this->belongsTo(Ancaman::class, 'ancaman_id');
    }

    public function sumberAncaman(): BelongsTo
    {
        return $this->belongsTo(SumberAncaman::class, 'sumber_ancaman_id');
    }

    public function kerentanan(): BelongsTo
    {
        return $this->belongsTo(Kerentanan::class, 'kerentanan_id');
    }

    public function pemilikRisiko(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pemilik_risiko_id');
    }

    public function risikoAset(): HasMany
    {
        return $this->hasMany(RisikoAset::class, 'risiko_id');
    }

    public function buktiKondisiAset(): HasMany
    {
        return $this->hasMany(BuktiKondisiAset::class, 'risiko_id');
    }

    public function penilaianRisiko(): HasMany
    {
        return $this->hasMany(PenilaianRisiko::class, 'risiko_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
