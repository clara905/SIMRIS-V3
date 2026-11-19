<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VersiMetodeRisiko;
use App\Services\RoleScopeService;
use LogicException;

class MetodeRisikoPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, VersiMetodeRisiko $versi): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, VersiMetodeRisiko $versi): bool
    {
        return $this->canManage($user);
    }

    public function freeze(User $user, VersiMetodeRisiko $versi): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
    {
        try {
            return in_array(
                $this->roleScopeService->getActiveRoleCode($user),
                [
                    'SUPER_ADMIN',
                    'ADMIN_SATKER',
                    'KASATKER',
                    'KABID',
                    'KASUB',
                    'STAFF',
                ],
                true
            );
        } catch (LogicException) {
            return false;
        }
    }

    private function canManage(User $user): bool
    {
        try {
            return $this->roleScopeService->hasGlobalAccess($user);
        } catch (LogicException) {
            return false;
        }
    }
}
