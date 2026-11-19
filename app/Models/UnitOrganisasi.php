<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'kode',
    'nama',
    'jenis',
    'aktif',
])]
class UnitOrganisasi extends Model
{
    protected $table = 'unit_organisasi';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function penempatanPegawai(): HasMany
    {
        return $this->hasMany(PenempatanPegawai::class, 'unit_id');
    }

    public function periodePenilaian(): HasMany
    {
        return $this->hasMany(PeriodePenilaian::class, 'unit_id');
    }

    public function asesmenRisiko(): HasMany
    {
        return $this->hasMany(AsesmenRisiko::class, 'unit_id');
    }
}
