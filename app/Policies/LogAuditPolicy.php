<?php

namespace App\Policies;

use App\Models\LogAudit;
use App\Models\User;
use App\Services\RoleScopeService;
use LogicException;

class LogAuditPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function view(User $user, LogAudit $logAudit): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function correct(User $user, LogAudit $logAudit): bool
    {
        return $this->isSuperAdmin($user)
            && $logAudit->koreksi_dari_id === null;
    }

    private function isSuperAdmin(User $user): bool
    {
        try {
            return $this->roleScopeService->hasGlobalAccess($user);
        } catch (LogicException) {
            return false;
        }
    }
}
