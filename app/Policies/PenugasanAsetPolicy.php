<?php

namespace App\Policies;

use App\Models\Aset;
use App\Models\PenugasanAset;
use App\Models\User;
use App\Services\AsetAccessService;
use App\Services\RoleScopeService;
use LogicException;

class PenugasanAsetPolicy
{
    public function __construct(
        private readonly AsetAccessService $asetAccessService,
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user, Aset $aset): bool
    {
        try {
            return $this->asetAccessService->canViewAsset($user, $aset);
        } catch (LogicException) {
            return false;
        }
    }

    public function create(User $user, Aset $aset): bool
    {
        try {
            return $this->roleScopeService->getActiveRoleCode($user) === 'KASUB'
                && $this->asetAccessService->canManageAsset($user, $aset);
        } catch (LogicException) {
            return false;
        }
    }

    public function end(User $user, PenugasanAset $penugasan): bool
    {
        try {
            $penugasan->loadMissing('aset');

            return $penugasan->aset !== null
                && $this->roleScopeService->getActiveRoleCode($user) === 'KASUB'
                && $this->asetAccessService->canManageAsset(
                    $user,
                    $penugasan->aset
                );
        } catch (LogicException) {
            return false;
        }
    }
}
