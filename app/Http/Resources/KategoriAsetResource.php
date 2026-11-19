<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KategoriAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'kode' => $this->kode,
            'nama' => $this->nama,

            'parent' => new self(
                $this->whenLoaded('parentKategori')
            ),

            'children' => self::collection(
                $this->whenLoaded('children')
            ),
        ];
    }
}
