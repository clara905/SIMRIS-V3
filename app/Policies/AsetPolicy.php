<?php

namespace App\Policies;

use App\Models\Aset;
use App\Models\User;
use App\Services\AsetAccessService;
use App\Services\RoleScopeService;
use LogicException;

class AsetPolicy
{
    public function __construct(
        private readonly AsetAccessService $asetAccessService,
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        try {
            return in_array(
                $this->roleScopeService->getActiveRoleCode($user),
                ['SUPER_ADMIN', 'KASUB', 'KABID', 'KASATKER'],
                true
            );
        } catch (LogicException) {
            return false;
        }
    }

    public function view(User $user, Aset $aset): bool
    {
        try {
            return $this->asetAccessService
                ->canViewAsset($user, $aset);
        } catch (LogicException) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        try {
            return $this->roleScopeService
                ->getActiveRoleCode($user) === 'KASUB';
        } catch (LogicException) {
            return false;
        }
    }

    public function update(User $user, Aset $aset): bool
    {
        try {
            return $this->asetAccessService
                ->canManageAsset($user, $aset);
        } catch (LogicException) {
            return false;
        }
    }

    public function delete(User $user, Aset $aset): bool
    {
        return $this->update($user, $aset);
    }
}
