<?php

namespace App\Policies;

use App\Models\Dokumen;
use App\Models\User;
use App\Services\AsetAccessService;
use App\Services\RoleScopeService;
use LogicException;

class DokumenPolicy
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

    public function view(User $user, Dokumen $dokumen): bool
    {
        try {
            $unit = $dokumen->unitPemilik;

            if (
                ! $unit ||
                ! $unit->aktif ||
                $unit->jenis !== 'SUBBIDANG'
            ) {
                return false;
            }

            $visibleIds = $this->asetAccessService
                ->getVisibleSubbidangIds($user);

            return $visibleIds === null
                || in_array(
                    (int) $dokumen->unit_pemilik_id,
                    $visibleIds,
                    true
                );
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
}
