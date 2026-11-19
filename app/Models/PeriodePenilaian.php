<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'unit_id',
    'dibuat_oleh',
    'nama_periode',
    'tanggal_mulai',
    'tanggal_selesai',
    'status',
    'dibuat_pada',
    'diperbarui_pada',
])]
class PeriodePenilaian extends Model
{
    protected $table = 'periode_penilaian';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'dibuat_oleh' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'dibuat_pada' => 'datetime',
            'diperbarui_pada' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function asesmen(): HasMany
    {
        return $this->hasMany(AsesmenRisiko::class, 'periode_id');
    }
}
