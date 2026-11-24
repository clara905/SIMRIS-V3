<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use LogicException;

class UserRoleScopePolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService
    ) {}

    public function viewHistory(
        User $actor,
        User $target
    ): bool {
        if ($actor->id === $target->id) {
            return false;
        }

        return $this->canManageTarget($actor, $target);
    }

    public function assignRole(
        User $actor,
        User $target,
        string $roleCode,
        ?int $unitId = null
    ): bool {
        if ($actor->id === $target->id || ! $target->aktif) {
            return false;
        }

        try {
            $actorRole = $this->roleScopeService
                ->getActiveRoleCode($actor);

            if (! in_array(
                $actorRole,
                ['SUPER_ADMIN', 'ADMIN_SATKER'],
                true
            )) {
                return false;
            }

            $isSuperAdmin = $this->roleScopeService
                ->hasGlobalAccess($actor);

            if ($actorRole === 'SUPER_ADMIN' && $isSuperAdmin) {
                return true;
            }

            if ($actorRole !== 'ADMIN_SATKER' || $isSuperAdmin) {
                return false;
            }

            // ADMIN_SATKER tidak boleh menunjuk administrator global
            // atau administrator SATKER lainnya.
            if (in_array(
                $roleCode,
                ['SUPER_ADMIN', 'ADMIN_SATKER'],
                true
            )) {
                return false;
            }

            if (! $this->canManageTarget($actor, $target)) {
                return false;
            }

            // Role dengan unit harus berada dalam cakupan aktor.
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($actor);

            if ($unitId !== null) {
                return $accessibleUnitIds !== null
                    && in_array(
                        $unitId,
                        $accessibleUnitIds,
                        true
                    );
            }

            // STAFF wajib memakai unit penempatan pegawai yang berada
            // dalam cakupan aktor.
            if ($roleCode === 'STAFF') {
                $targetUnitId = $this->getTargetEffectiveUnitId($target);

                return $targetUnitId !== null
                    && $accessibleUnitIds !== null
                    && in_array(
                        $targetUnitId,
                        $accessibleUnitIds,
                        true
                    );
            }

            return false;
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Memastikan target berada dalam cakupan unit aktor.
     */
    private function canManageTarget(
        User $actor,
        User $target
    ): bool {
        if (! $target->aktif) {
            return false;
        }

        $actorRole = $this->roleScopeService
            ->getActiveRoleCode($actor);

        $isSuperAdmin = $this->roleScopeService
            ->hasGlobalAccess($actor);

        if ($actorRole === 'SUPER_ADMIN' && $isSuperAdmin) {
            return true;
        }

        if ($actorRole !== 'ADMIN_SATKER') {
            return false;
        }

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($actor);

        if ($accessibleUnitIds === null || $accessibleUnitIds === []) {
            return false;
        }

        $targetUnitId = $this->getTargetEffectiveUnitId($target);

        return $targetUnitId !== null
            && in_array(
                $targetUnitId,
                $accessibleUnitIds,
                true
            );
    }

    /**
     * Mengambil unit efektif target melalui mekanisme role scope yang sama.
     */
    private function getTargetEffectiveUnitId(User $target): ?int
    {
        return $this->roleScopeService->getTargetEffectiveUnitId($target);
    }
}
