<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DokumenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unit_pemilik_id' => $this->unit_pemilik_id,
            'unit_pemilik' => $this->whenLoaded(
                'unitPemilik',
                fn () => [
                    'id' => $this->unitPemilik->id,
                    'kode' => $this->unitPemilik->kode,
                    'nama' => $this->unitPemilik->nama,
                ]
            ),
            'dibuat_oleh' => $this->dibuat_oleh,
            'pembuat' => $this->whenLoaded(
                'pembuat',
                fn () => [
                    'id' => $this->pembuat->id,
                    'username' => $this->pembuat->username,
                ]
            ),
            'kode' => $this->kode,
            'judul' => $this->judul,
            'jenis' => $this->jenis,
            'nama_file_asli' => $this->nama_file_asli,
            'ekstensi' => $this->ekstensi,
            'mime_type' => $this->mime_type,
            'ukuran_byte' => $this->ukuran_byte,
            'checksum_sha256' => $this->checksum_sha256,
            'status_pemeriksaan' => $this->status_pemeriksaan,
            'dibuat_pada' => $this->dibuat_pada,
        ];
    }
}
