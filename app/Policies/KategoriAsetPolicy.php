<?php

namespace App\Policies;

use App\Models\KategoriAset;
use App\Models\User;
use App\Services\RoleScopeService;
use LogicException;

class KategoriAsetPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, KategoriAset $kategori): bool
    {
        return $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, KategoriAset $kategori): bool
    {
        return $this->canManage($user);
    }

    public function delete(User $user, KategoriAset $kategori): bool
    {
        return $this->canManage($user);
    }

    private function canView(User $user): bool
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

    private function canManage(User $user): bool
    {
        try {
            return $this->roleScopeService
                ->getActiveRoleCode($user) === 'KASUB';
        } catch (LogicException) {
            return false;
        }
    }
}
