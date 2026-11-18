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
])]
class KategoriAset extends Model
{
    protected $table = 'kategori_aset';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
        ];
    }

    public function parentKategori(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
