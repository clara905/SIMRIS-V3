<?php

namespace App\Policies;

use App\Models\PenempatanPegawai;
use App\Models\PengajuanAkun;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use LogicException;

class PengajuanAkunPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService
    ) {}

    public function viewAny(User $actor): bool
    {
        return $this->canReview($actor);
    }

    public function view(
        User $actor,
        PengajuanAkun $pengajuan
    ): bool {
        return $this->canReview($actor)
            && $this->canAccessSubmission($actor, $pengajuan);
    }

    public function approve(
        User $actor,
        PengajuanAkun $pengajuan,
        string $roleCode,
        ?int $unitId = null
    ): bool {
        if (! $this->view($actor, $pengajuan)) {
            return false;
        }

        try {
            if ($pengajuan->status !== PengajuanAkun::STATUS_MENUNGGU) {
                return false;
            }

            $actorRole = $this->roleScopeService
                ->getActiveRoleCode($actor);

            $isSuperAdmin = $this->roleScopeService
                ->hasGlobalAccess($actor);

            if (
                $actorRole === 'SUPER_ADMIN'
                && $isSuperAdmin
            ) {
                return $this->validRoleUnit(
                    $pengajuan,
                    $roleCode,
                    $unitId
                );
            }

            if ($actorRole !== 'ADMIN_SATKER' || $isSuperAdmin) {
                return false;
            }

            if (in_array(
                $roleCode,
                ['SUPER_ADMIN', 'ADMIN_SATKER'],
                true
            )) {
                return false;
            }

            $accessibleIds = $this->unitAccessService
                ->getAccessibleUnitIds($actor);

            $placement = $this->activePlacement($pengajuan);

            if (! $placement || ! in_array(
                (int) $placement->unit_id,
                $accessibleIds ?? [],
                true
            )) {
                return false;
            }

            if ($roleCode === 'STAFF') {
                return $unitId === null;
            }

            return $unitId !== null
                && in_array($unitId, $accessibleIds ?? [], true)
                && $this->validRoleUnit(
                    $pengajuan,
                    $roleCode,
                    $unitId
                );
        } catch (LogicException) {
            return false;
        }
    }

    public function reject(
        User $actor,
        PengajuanAkun $pengajuan
    ): bool {
        return $this->view($actor, $pengajuan);
    }

    private function canReview(User $actor): bool
    {
        try {
            $role = $this->roleScopeService
                ->getActiveRoleCode($actor);

            if ($role === 'SUPER_ADMIN') {
                return $this->roleScopeService
                    ->hasGlobalAccess($actor);
            }

            if ($role !== 'ADMIN_SATKER') {
                return false;
            }

            $unitIds = $this->unitAccessService
                ->getAccessibleUnitIds($actor);

            return $unitIds !== null && $unitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }

    private function canAccessSubmission(
        User $actor,
        PengajuanAkun $pengajuan
    ): bool {
        try {
            if (
                $this->roleScopeService->getActiveRoleCode($actor)
                    === 'SUPER_ADMIN'
                && $this->roleScopeService->hasGlobalAccess($actor)
            ) {
                return true;
            }

            $unitIds = $this->unitAccessService
                ->getAccessibleUnitIds($actor);

            if (! $unitIds) {
                return false;
            }

            $placement = $this->activePlacement($pengajuan);

            return $placement !== null
                && in_array(
                    (int) $placement->unit_id,
                    $unitIds,
                    true
                );
        } catch (LogicException) {
            return false;
        }
    }

    private function activePlacement(
        PengajuanAkun $pengajuan
    ): ?PenempatanPegawai {
        $pegawaiId = $pengajuan->user?->pegawai_id;

        if (! $pegawaiId) {
            return null;
        }

        $placements = PenempatanPegawai::query()
            ->where('pegawai_id', $pegawaiId)
            ->whereDate('mulai', '<=', today())
            ->where(function ($query) {
                $query->whereNull('selesai')
                    ->orWhereDate('selesai', '>=', today());
            })
            ->whereHas('unit', fn ($query) => (
                $query->where('aktif', true)
            ))
            ->get();

        return $placements->count() === 1
            ? $placements->first()
            : null;
    }

    private function validRoleUnit(
        PengajuanAkun $pengajuan,
        string $roleCode,
        ?int $unitId
    ): bool {
        $allowedTypes = [
            'ADMIN_SATKER' => 'SATKER',
            'KASATKER' => 'SATKER',
            'KABID' => 'BIDANG',
            'KASUB' => 'SUBBIDANG',
        ];

        if ($roleCode === 'SUPER_ADMIN' || $roleCode === 'STAFF') {
            return $unitId === null;
        }

        if (! isset($allowedTypes[$roleCode]) || $unitId === null) {
            return false;
        }

        $unit = UnitOrganisasi::query()
            ->whereKey($unitId)
            ->where('aktif', true)
            ->first();

        if (! $unit || $unit->jenis !== $allowedTypes[$roleCode]) {
            return false;
        }

        $placement = $this->activePlacement($pengajuan);

        if (! $placement) {
            return false;
        }

        // Unit penugasan harus merupakan unit penempatan
        // atau salah satu induknya.
        $currentUnitId = (int) $placement->unit_id;
        $visited = [];

        while ($currentUnitId !== 0) {
            if (isset($visited[$currentUnitId])) {
                return false;
            }

            $visited[$currentUnitId] = true;

            if ($currentUnitId === $unitId) {
                return true;
            }

            $currentUnitId = (int) (
                UnitOrganisasi::query()
                    ->whereKey($currentUnitId)
                    ->value('parent_id') ?? 0
            );
        }

        return false;
    }
}
