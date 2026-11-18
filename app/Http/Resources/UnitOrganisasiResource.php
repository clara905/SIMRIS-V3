<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitOrganisasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'aktif' => $this->aktif,

            'parent' => new self(
                $this->whenLoaded('parentUnit')
            ),

            'children' => self::collection(
                $this->whenLoaded('children')
            ),
        ];
    }
}
