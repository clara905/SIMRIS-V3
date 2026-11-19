<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'idx' => $this->idx,
            'sumber_id' => $this->sumber_id,
            'external_id' => $this->external_id,
            'unit_pemilik_id' => $this->unit_pemilik_id,
            'kategori_id' => $this->kategori_id,
            'klasifikasi_informasi_id' => $this->klasifikasi_informasi_id,
            'kode_aset' => $this->kode_aset,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'merk' => $this->merk,
            'nomor_seri' => $this->nomor_seri,
            'jumlah' => $this->jumlah,
            'kondisi' => $this->kondisi,
            'lokasi' => $this->lokasi,
            'tanggal_pengadaan' => $this->tanggal_pengadaan?->toDateString(),
            'status' => $this->status,
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'dibuat_pada' => $this->dibuat_pada?->toISOString(),
            'diperbarui_pada' => $this->diperbarui_pada?->toISOString(),

            'sumber' => new SumberAsetResource(
                $this->whenLoaded('sumberAset')
            ),

            'unit_pemilik' => new UnitOrganisasiResource(
                $this->whenLoaded('unitPemilik')
            ),

            'kategori' => new KategoriAsetResource(
                $this->whenLoaded('kategori')
            ),

            'klasifikasi_informasi' => new KlasifikasiInformasiResource(
                $this->whenLoaded('klasifikasiInformasi')
            ),
        ];
    }
}
