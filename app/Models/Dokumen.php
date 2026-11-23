<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'unit_pemilik_id',
    'dibuat_oleh',
    'kode',
    'judul',
    'jenis',
    'nama_file_asli',
    'storage_key',
    'ekstensi',
    'mime_type',
    'ukuran_byte',
    'checksum_sha256',
    'status_pemeriksaan',
    'dibuat_pada',
])]
class Dokumen extends Model
{
    protected $table = 'dokumen';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unit_pemilik_id' => 'integer',
            'dibuat_oleh' => 'integer',
            'ukuran_byte' => 'integer',
            'dibuat_pada' => 'datetime',
        ];
    }

    public function unitPemilik(): BelongsTo
    {
        return $this->belongsTo(
            UnitOrganisasi::class,
            'unit_pemilik_id'
        );
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'dibuat_oleh'
        );
    }

    public function buktiKondisiAset(): HasMany
    {
        return $this->hasMany(BuktiKondisiAset::class, 'dokumen_id');
    }
}
