<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleScopeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role?->id,
                'kode' => $this->role?->kode,
                'nama' => $this->role?->nama,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => $this->unit ? [
                'id' => $this->unit->id,
                'kode' => $this->unit->kode,
                'nama' => $this->unit->nama,
                'jenis' => $this->unit->jenis,
            ] : null),
            'mulai' => $this->mulai?->toISOString(),
            'selesai' => $this->selesai?->toISOString(),
            'aktif' => $this->selesai === null,
        ];
    }
}
