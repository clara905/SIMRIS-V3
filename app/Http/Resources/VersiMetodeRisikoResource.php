<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersiMetodeRisikoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_versi' => $this->kode_versi,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'dibekukan' => $this->dibekukan,
            'dibekukan_pada' => $this->dibekukan_pada?->toDateTimeString(),

            'skala_kemungkinan' => $this->whenLoaded(
                'skalaKemungkinan',
                fn () => $this->skalaKemungkinan->map(fn ($item) => [
                    'id' => $item->id,
                    'nilai' => $item->nilai,
                    'nama' => $item->nama,
                    'deskripsi' => $item->deskripsi,
                ])->values()
            ),

            'skala_dampak' => $this->whenLoaded(
                'skalaDampak',
                fn () => $this->skalaDampak->map(fn ($item) => [
                    'id' => $item->id,
                    'nilai' => $item->nilai,
                    'nama' => $item->nama,
                    'deskripsi' => $item->deskripsi,
                ])->values()
            ),

            'tingkat_risiko' => $this->whenLoaded(
                'tingkatRisiko',
                fn () => $this->tingkatRisiko
                    ->sortBy('urutan')
                    ->values()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'urutan' => $item->urutan,
                    ])
            ),

            'matriks' => $this->whenLoaded(
                'matriksRisiko',
                fn () => $this->matriksRisiko->map(fn ($item) => [
                    'id' => $item->id,
                    'kemungkinan' => [
                        'id' => $item->kemungkinan?->id,
                        'nilai' => $item->kemungkinan?->nilai,
                        'nama' => $item->kemungkinan?->nama,
                    ],
                    'dampak' => [
                        'id' => $item->dampak?->id,
                        'nilai' => $item->dampak?->nilai,
                        'nama' => $item->dampak?->nama,
                    ],
                    'tingkat_risiko' => [
                        'id' => $item->tingkatRisiko?->id,
                        'kode' => $item->tingkatRisiko?->kode,
                        'nama' => $item->tingkatRisiko?->nama,
                        'urutan' => $item->tingkatRisiko?->urutan,
                    ],
                    'skor' => $item->skor,
                    'rekomendasi_keputusan' => $item->rekomendasi_keputusan,
                ])->values()
            ),
        ];
    }
}
