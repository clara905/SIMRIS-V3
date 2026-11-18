<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use Illuminate\Support\Carbon;
use LogicException;

class UserPolicy
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Melihat daftar pengguna sesuai cakupan unit.
     */
    public function viewAny(User $user): bool
    {
        try {
            $roleCode = $this->roleScopeService
                ->getActiveRoleCode($user);

            if (! in_array($roleCode, [
                'SUPER_ADMIN',
                'ADMIN_SATKER',
                'KASATKER',
                'KABID',
                'KASUB',
            ], true)) {
                return false;
            }

            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            return $accessibleUnitIds === null
                || $accessibleUnitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Melihat detail pengguna sesuai cakupan unit.
     */
    public function view(User $user, User $target): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            // SUPER_ADMIN memiliki akses global.
            if ($accessibleUnitIds === null) {
                return true;
            }

            if ($accessibleUnitIds === []) {
                return false;
            }

            return $this->targetIsWithinScope(
                $target,
                $accessibleUnitIds
            );
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Memastikan pengguna target berada dalam cakupan unit aktor.
     */
    private function targetIsWithinScope(
        User $target,
        array $accessibleUnitIds
    ): bool {
        $scopes = $target->roleScopes()
            ->with('role')
            ->where('mulai', '<=', now())
            ->whereNull('selesai')
            ->get();

        // Lebih dari satu role aktif berarti data tidak valid.
        if ($scopes->count() > 1) {
            return false;
        }

        if ($scopes->count() === 1) {
            $scope = $scopes->first();
            $roleCode = $scope->role?->kode;

            // Akun SUPER_ADMIN tidak termasuk cakupan administrator unit.
            if ($roleCode === 'SUPER_ADMIN') {
                return false;
            }

            // STAFF mengikuti unit penempatan pegawainya.
            if ($roleCode !== 'STAFF') {
                return $scope->unit_id !== null
                    && in_array(
                        $scope->unit_id,
                        $accessibleUnitIds,
                        true
                    )
                    && $scope->unit()
                        ->where('aktif', true)
                        ->exists();
            }
        }

        /*
         * Akun tanpa role aktif, termasuk akun yang menunggu persetujuan,
         * menggunakan penempatan pegawai sebagai dasar cakupan.
         */
        $pegawai = $target->pegawai;

        if (! $pegawai || ! $pegawai->aktif) {
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
    }
}
