<?php

namespace App\Policies;

use App\Models\Risiko;
use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use LogicException;

class RisikoPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService,
    ) {}

    public function viewPenilaian(User $user, Risiko $risiko): bool
    {
        try {
            $role = $this->roleScopeService->getActiveRoleCode($user);

            if (! in_array($role, [
                'SUPER_ADMIN',
                'ADMIN_SATKER',
                'KASATKER',
                'KABID',
                'KASUB',
                'STAFF',
            ], true)) {
                return false;
            }

            $unitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            if ($unitIds === null) {
                return true;
            }

            $unitId = $risiko->asesmen?->unit_id;

            return $unitId !== null
                && in_array((int) $unitId, $unitIds, true);
        } catch (LogicException) {
            return false;
        }
    }

    public function createPenilaian(User $user, Risiko $risiko): bool
    {
        try {
            if (
                $this->roleScopeService->getActiveRoleCode($user)
                !== 'STAFF'
            ) {
                return false;
            }

            $unitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            if ($unitIds === null) {
                return false;
            }

            $unitId = $risiko->asesmen?->unit_id;

            return $unitId !== null
                && in_array((int) $unitId, $unitIds, true);
        } catch (LogicException) {
            return false;
        }
    }
}
