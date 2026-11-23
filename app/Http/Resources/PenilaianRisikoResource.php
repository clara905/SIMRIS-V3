<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenilaianRisikoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'risiko_id' => $this->risiko_id,
            'jenis_penilaian' => $this->jenis_penilaian,

            'alasan_kemungkinan' => $this->alasan_kemungkinan,
            'alasan_dampak' => $this->alasan_dampak,
            'kesimpulan' => $this->kesimpulan,

            'dinilai_pada' => $this->dinilai_pada,

            'penilai' => $this->whenLoaded(
                'penilai',
                fn () => [
                    'id' => $this->penilai?->id,
                    'username' => $this->penilai?->username,
                    'nama' => $this->penilai?->pegawai?->nama
                        ?? $this->penilai?->username,
                ]
            ),

            'matriks' => $this->whenLoaded(
                'matriks',
                fn () => [
                    'id' => $this->matriks?->id,
                    'skor' => $this->matriks?->skor,
                    'rekomendasi_keputusan' => $this->matriks?->rekomendasi_keputusan,

                    'kemungkinan' => [
                        'id' => $this->matriks?->kemungkinan?->id,
                        'nilai' => $this->matriks?->kemungkinan?->nilai,
                        'nama' => $this->matriks?->kemungkinan?->nama,
                    ],

                    'dampak' => [
                        'id' => $this->matriks?->dampak?->id,
                        'nilai' => $this->matriks?->dampak?->nilai,
                        'nama' => $this->matriks?->dampak?->nama,
                    ],

                    'tingkat_risiko' => [
                        'id' => $this->matriks?->tingkatRisiko?->id,
                        'kode' => $this->matriks?->tingkatRisiko?->kode,
                        'nama' => $this->matriks?->tingkatRisiko?->nama,
                        'urutan' => $this->matriks?->tingkatRisiko?->urutan,
                    ],
                ]
            ),
        ];
    }
}
