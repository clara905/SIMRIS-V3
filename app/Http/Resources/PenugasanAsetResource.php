<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenugasanAsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aset_idx' => $this->aset_idx,
            'pegawai' => $this->whenLoaded('pegawai', function () {
                return [
                    'id' => $this->pegawai->id,
                    'nip' => $this->pegawai->nip,
                    'nama' => $this->pegawai->nama,
                    'aktif' => $this->pegawai->aktif,
                ];
            }),
            'jenis_tanggung_jawab' => $this->jenis_tanggung_jawab,
            'mulai' => $this->mulai?->toDateString(),
            'selesai' => $this->selesai?->toDateString(),
            'status' => $this->selesai === null ? 'AKTIF' : 'SELESAI',
        ];
    }
}
