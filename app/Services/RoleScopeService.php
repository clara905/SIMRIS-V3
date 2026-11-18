<?php

namespace App\Services;

use App\Models\PenempatanPegawai;
use App\Models\User;
use App\Models\UserRoleScope;
use LogicException;

class RoleScopeService
{
    /**
     * Mengambil satu role yang sedang aktif.
     */
    public function getActiveScope(User $user): ?UserRoleScope
    {
        if (! $user->aktif) {
            return null;
        }

        // Jika akun terhubung dengan pegawai, pegawai wajib aktif.
        if ($user->pegawai_id !== null) {
            $pegawai = $user->pegawai;

            if (! $pegawai || ! $pegawai->aktif) {
                return null;
            }
        }

        $scopes = $user->roleScopes()
            ->with(['role', 'unit'])
            ->where('mulai', '<=', now())
            ->whereNull('selesai')
            ->get();

        if ($scopes->count() > 1) {
            throw new LogicException(
                'User memiliki lebih dari satu role aktif.'
            );
        }

        return $scopes->first();
    }

    /**
     * Mengambil kode role aktif.
     */
    public function getActiveRoleCode(User $user): ?string
    {
        return $this->getActiveScope($user)?->role?->kode;
    }

    /**
     * Memeriksa apakah user merupakan SUPER_ADMIN aktif.
     */
    public function hasGlobalAccess(User $user): bool
    {
        $scope = $this->getActiveScope($user);

        if (! $scope) {
            return false;
        }

        if ($scope->role?->kode !== 'SUPER_ADMIN') {
            return false;
        }

        if ($scope->unit_id !== null) {
            throw new LogicException(
                'SUPER_ADMIN tidak boleh memiliki unit_id.'
            );
        }

        return true;
    }

    /**
     * Mengambil unit akses efektif berdasarkan role.
     *
     * SUPER_ADMIN: tidak memiliki unit spesifik.
     * STAFF: berdasarkan penempatan aktif.
     * Role lainnya: berdasarkan user_role_scope.unit_id.
     */
    public function getEffectiveUnitId(User $user): ?int
    {
        $scope = $this->getActiveScope($user);

        if (! $scope) {
            return null;
        }

        $roleCode = $scope->role?->kode;

        if ($roleCode === 'SUPER_ADMIN') {
            if ($scope->unit_id !== null) {
                throw new LogicException(
                    'SUPER_ADMIN tidak boleh memiliki unit_id.'
                );
            }

            return null;
        }

        if ($roleCode === 'STAFF') {
            if ($scope->unit_id !== null) {
                throw new LogicException(
                    'STAFF harus mengambil unit dari penempatan aktif.'
                );
            }

            return $this->getActivePlacementUnitId($user);
        }

        if ($scope->unit_id === null) {
            throw new LogicException(
                'Role selain SUPER_ADMIN dan STAFF wajib memiliki unit_id.'
            );
        }

        if (! $scope->unit || ! $scope->unit->aktif) {
            return null;
        }

        return (int) $scope->unit_id;
    }

    /**
     * Mengambil unit dari penempatan pegawai yang masih aktif.
     */
    private function getActivePlacementUnitId(User $user): ?int
    {
        if (! $user->pegawai_id || ! $user->pegawai?->aktif) {
            return null;
        }

        $placements = PenempatanPegawai::query()
            ->with('unit')
            ->where('pegawai_id', $user->pegawai_id)
            ->whereDate('mulai', '<=', today())
            ->whereNull('selesai')
            ->get();

        if ($placements->count() > 1) {
            throw new LogicException(
                'Pegawai memiliki lebih dari satu penempatan aktif.'
            );
        }

        $placement = $placements->first();

        if (! $placement || ! $placement->unit?->aktif) {
            return null;
        }

        return (int) $placement->unit_id;
    }
}
