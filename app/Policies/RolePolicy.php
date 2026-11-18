<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RoleScopeService;
use LogicException;

class RolePolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        try {
            $roleCode = $this->roleScopeService
                ->getActiveRoleCode($user);

            return in_array(
                $roleCode,
                ['SUPER_ADMIN', 'ADMIN_SATKER'],
                true
            );
        } catch (LogicException) {
            return false;
        }
    }
}
