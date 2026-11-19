<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AsetAccessService
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Mengambil ID SUBBIDANG yang asetnya boleh dilihat pengguna.
     *
     * NULL berarti akses seluruh SUBBIDANG (SUPER_ADMIN).
     */
    public function getVisibleSubbidangIds(User $user): ?array
    {
        $roleCode = $this->roleScopeService->getActiveRoleCode($user);

        if (! in_array($roleCode, [
            'SUPER_ADMIN',
            'KASUB',
            'KABID',
            'KASATKER',
        ], true)) {
            return [];
        }

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($user);

        $query = UnitOrganisasi::query()
            ->where('aktif', true)
            ->where('jenis', 'SUBBIDANG');

        if ($accessibleUnitIds === null) {
            return null;
        }

        if ($accessibleUnitIds === []) {
            return [];
        }

        return $query
            ->whereIn('id', $accessibleUnitIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Membatasi query aset berdasarkan SUBBIDANG yang dapat dilihat.
     */
    public function scopeVisibleAssets(
        Builder $query,
        User $user
    ): Builder {
        $unitIds = $this->getVisibleSubbidangIds($user);

        if ($unitIds === null) {
            return $query;
        }

        return $query->whereIn('unit_pemilik_id', $unitIds);
    }

    /**
     * Memastikan pengguna boleh melihat aset tertentu.
     */
    public function canViewAsset(User $user, Aset $aset): bool
    {
        $unit = $aset->unitPemilik;

        if (
            ! $unit ||
            ! $unit->aktif ||
            $unit->jenis !== 'SUBBIDANG'
        ) {
            return false;
        }

        $visibleIds = $this->getVisibleSubbidangIds($user);

        return $visibleIds !== null
            && in_array((int) $aset->unit_pemilik_id, $visibleIds, true)
            || $visibleIds === null;
    }

    /**
     * Memastikan unit tujuan merupakan SUBBIDANG aktif
     * yang tepat sama dengan scope KASUB.
     */
    public function canManageUnit(User $user, int $unitId): bool
    {
        if (
            $this->roleScopeService->getActiveRoleCode($user) !== 'KASUB'
        ) {
            return false;
        }

        $effectiveUnitId = $this->roleScopeService
            ->getEffectiveUnitId($user);

        if ($effectiveUnitId === null || $effectiveUnitId !== $unitId) {
            return false;
        }

        return UnitOrganisasi::query()
            ->whereKey($unitId)
            ->where('jenis', 'SUBBIDANG')
            ->where('aktif', true)
            ->exists();
    }

    /**
     * Memastikan KASUB hanya mengubah aset milik SUBBIDANG-nya.
     */
    public function canManageAsset(User $user, Aset $aset): bool
    {
        return $this->canManageUnit(
            $user,
            (int) $aset->unit_pemilik_id
        );
    }
}
