<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use Illuminate\Support\Carbon;
use LogicException;

class PegawaiPolicy
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService,
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function viewAny(User $user): bool
    {
        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            return $accessibleUnitIds === null
                || $accessibleUnitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            if ($accessibleUnitIds === null) {
                return true;
            }

            if ($accessibleUnitIds === []) {
                return false;
            }

            return $pegawai->penempatan()
                ->whereDate('mulai', '<=', Carbon::today())
                ->where(function ($query) {
                    $query->whereNull('selesai')
                        ->orWhereDate('selesai', '>=', Carbon::today());
                })
                ->whereIn('unit_id', $accessibleUnitIds)
                ->whereHas('unit', function ($query) {
                    $query->where('aktif', true);
                })
                ->exists();
        } catch (LogicException) {
            return false;
        }
    }

    public function create(User $user): bool
    {
        return $this->canManagePegawai($user);
    }

    public function update(User $user, Pegawai $pegawai): bool
    {
        return $this->canManagePegawai($user)
            && $this->view($user, $pegawai);
    }

    public function deactivate(User $user, Pegawai $pegawai): bool
    {
        return $this->canManagePegawai($user)
            && $this->view($user, $pegawai);
    }

    private function canManagePegawai(User $user): bool
    {
        try {
            $roleCode = $this->roleScopeService
                ->getActiveRoleCode($user);

            if (! in_array($roleCode, [
                'SUPER_ADMIN',
                'ADMIN_SATKER',
            ], true)) {
                return false;
            }

            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            if ($roleCode === 'SUPER_ADMIN') {
                return $accessibleUnitIds === null;
            }

            return $accessibleUnitIds !== null
                && $accessibleUnitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }
}
