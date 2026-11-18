<?php

namespace App\Http\Resources;

use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class PengajuanAkunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;
        $pegawai = $user?->pegawai;

        $today = today();
        $placements = $pegawai?->penempatan ?? collect();

        $actor = $request->user();

        $roleScopeService = app(RoleScopeService::class);
        $unitAccessService = app(UnitAccessService::class);

        $canViewAllHistory = false;
        $visibleUnitIds = [];

        if ($actor) {
            try {
                $canViewAllHistory = $roleScopeService
                    ->hasGlobalAccess($actor);

                if (! $canViewAllHistory) {
                    $visibleUnitIds = $unitAccessService
                        ->getAccessibleUnitIds($actor) ?? [];
                }
            } catch (LogicException) {
                $visibleUnitIds = [];
            }
        }

        $activePlacements = $placements->filter(
            fn ($placement) => $placement->mulai
                && $placement->mulai->lte($today)
                && (
                    $placement->selesai === null
                    || $placement->selesai->gte($today)
                )
                && $placement->unit?->aktif
        );

        $activePlacement = $activePlacements->count() === 1
            ? $activePlacements->first()
            : null;

        $visiblePlacements = $placements
            ->filter(
                fn ($placement) => $canViewAllHistory
                    || in_array(
                        (int) $placement->unit_id,
                        $visibleUnitIds,
                        true
                    )
            )
            ->sortByDesc('mulai')
            ->values();

        return [
            'id' => $this->id,
            'status' => $this->status,
            'catatan' => $this->catatan,
            'diajukan_pada' => $this->diajukan_pada,
            'ditinjau_pada' => $this->ditinjau_pada,

            'akun' => [
                'id' => $user?->id,
                'username' => $user?->username,
                'email' => $user?->email,
                'aktif' => $user?->aktif,
            ],

            'pegawai' => $pegawai ? [
                'id' => $pegawai->id,
                'nip' => $pegawai->nip,
                'nama' => $pegawai->nama,
                'email_kontak' => $pegawai->email_kontak,
                'jenis_kepegawaian' => $pegawai->jenis_kepegawaian,
                'aktif' => $pegawai->aktif,
            ] : null,

            'penempatan_aktif' => $activePlacement ? [
                'id' => $activePlacement->id,
                'mulai' => $activePlacement->mulai,
                'selesai' => $activePlacement->selesai,
                'unit' => [
                    'id' => $activePlacement->unit->id,
                    'kode' => $activePlacement->unit->kode,
                    'nama' => $activePlacement->unit->nama,
                    'jenis' => $activePlacement->unit->jenis,
                ],
            ] : null,

            'jumlah_penempatan_aktif' => $activePlacements->count(),

            'riwayat_penempatan' => $visiblePlacements
                ->map(fn ($placement) => [
                    'id' => $placement->id,
                    'mulai' => $placement->mulai,
                    'selesai' => $placement->selesai,
                    'unit' => $placement->unit ? [
                        'id' => $placement->unit->id,
                        'kode' => $placement->unit->kode,
                        'nama' => $placement->unit->nama,
                        'jenis' => $placement->unit->jenis,
                    ] : null,
                ]),

            'peninjau' => $this->peninjau ? [
                'id' => $this->peninjau->id,
                'username' => $this->peninjau->username,
                'nama' => $this->peninjau->pegawai?->nama,
            ] : null,
        ];
    }
}
