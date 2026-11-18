<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PegawaiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'nama' => $this->nama,
            'email_kontak' => $this->email_kontak,
            'jenis_kepegawaian' => $this->jenis_kepegawaian,
            'aktif' => $this->aktif,

            'penempatan' => $this->whenLoaded(
                'penempatan',
                function () {
                    $placement = $this->penempatan->first();

                    if (! $placement) {
                        return null;
                    }

                    return [
                        'id' => $placement->id,
                        'unit_id' => $placement->unit_id,
                        'unit' => $placement->unit
                            ? [
                                'kode' => $placement->unit->kode,
                                'nama' => $placement->unit->nama,
                                'jenis' => $placement->unit->jenis,
                            ]
                            : null,
                        'mulai' => $placement->mulai?->toDateString(),
                        'selesai' => $placement->selesai?->toDateString(),
                    ];
                }
            ),
        ];
    }
}
