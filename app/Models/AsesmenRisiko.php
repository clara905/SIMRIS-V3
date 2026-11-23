<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'seri_asesmen_id',
    'versi_sebelumnya_id',
    'periode_id',
    'unit_id',
    'versi_metode_id',
    'dibuat_oleh',
    'tujuan',
    'ruang_lingkup',
    'status',
    'nomor_versi',
    'dibuat_pada',
    'dibekukan_pada',
])]
class AsesmenRisiko extends Model
{
    protected $table = 'asesmen_risiko';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'seri_asesmen_id' => 'integer',
            'versi_sebelumnya_id' => 'integer',
            'periode_id' => 'integer',
            'unit_id' => 'integer',
            'versi_metode_id' => 'integer',
            'dibuat_oleh' => 'integer',
            'nomor_versi' => 'integer',
            'dibuat_pada' => 'datetime',
            'dibekukan_pada' => 'datetime',
        ];
    }

    public function seri(): BelongsTo
    {
        return $this->belongsTo(SeriAsesmen::class, 'seri_asesmen_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePenilaian::class, 'periode_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOrganisasi::class, 'unit_id');
    }

    public function versiMetode(): BelongsTo
    {
        return $this->belongsTo(
            VersiMetodeRisiko::class,
            'versi_metode_id'
        );
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function versiSebelumnya(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'versi_sebelumnya_id'
        );
    }

    public function revisiBerikutnya(): HasMany
    {
        return $this->hasMany(
            self::class,
            'versi_sebelumnya_id'
        );
    }

    public function risiko(): HasMany
    {
        return $this->hasMany(Risiko::class, 'asesmen_id');
    }
}
