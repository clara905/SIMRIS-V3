<?php

namespace App\Policies;

use App\Models\User;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use Illuminate\Support\Carbon;
use LogicException;

class UserPolicy
{
    private const STATUS_MANAGEMENT_ROLES = [
        'SUPER_ADMIN',
        'ADMIN_SATKER',
        'KASATKER',
        'KABID',
        'KASUB',
    ];

    private const ALLOWED_TARGET_ROLES = [
        'SUPER_ADMIN',
        'ADMIN_SATKER',
        'KASATKER',
        'KABID',
        'KASUB',
        'STAFF',
    ];

    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Menentukan apakah pengguna boleh melihat daftar akun.
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
     * Menentukan apakah pengguna boleh melihat detail akun.
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
     * Menentukan apakah akun boleh dinonaktifkan.
     */
    public function deactivate(User $user, User $target): bool
    {
        return $this->canManageStatus($user, $target);
    }

    /**
     * Menentukan apakah akun boleh diaktifkan kembali.
     */
    public function reactivate(User $user, User $target): bool
    {
        return $this->canManageStatus($user, $target);
    }

    /**
     * Validasi umum kewenangan pengelolaan status akun.
     */
    private function canManageStatus(
        User $user,
        User $target
    ): bool {
        // Pengguna tidak boleh mengelola status akunnya sendiri.
        if ($user->id === $target->id) {
            return false;
        }

        try {
            $actorRole = $this->roleScopeService
                ->getActiveRoleCode($user);

            if (! in_array(
                $actorRole,
                self::STATUS_MANAGEMENT_ROLES,
                true
            )) {
                return false;
            }

            /*
             * Target harus mempunyai tepat satu role
             * yang belum berakhir.
             *
             * Role masa depan juga dihitung agar kondisi
             * konsisten dengan UserAdministrationService.
             */
            $scopes = $target->roleScopes()
                ->with('role')
                ->whereNull('selesai')
                ->get();

            if ($scopes->count() !== 1) {
                return false;
            }

            $scope = $scopes->first();

            // Role yang belum berlaku tidak boleh dikelola.
            if ($scope->mulai->gt(now())) {
                return false;
            }

            $targetRole = $scope->role?->kode;

            if (! in_array(
                $targetRole,
                self::ALLOWED_TARGET_ROLES,
                true
            )) {
                return false;
            }

            /*
             * Pemeriksaan view memastikan:
             * - Target berada dalam cakupan unit aktor.
             * - Admin unit tidak dapat mengelola SUPER_ADMIN.
             * - Penempatan STAFF masih berlaku.
             */
            return $this->view($user, $target);
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Memeriksa apakah target berada dalam cakupan unit aktor.
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

        // Target dengan beberapa role aktif tidak valid.
        if ($scopes->count() > 1) {
            return false;
        }

        if ($scopes->count() === 1) {
            $scope = $scopes->first();
            $roleCode = $scope->role?->kode;

            // Admin unit tidak boleh mengelola SUPER_ADMIN.
            if ($roleCode === 'SUPER_ADMIN') {
                return false;
            }

            // Role selain STAFF menggunakan unit pada role scope.
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
         * Akun tanpa role aktif atau STAFF diperiksa
         * melalui penempatan pegawainya.
         */
        $pegawai = $target->pegawai;

        if (! $pegawai || ! $pegawai->aktif) {
            return false;
        }

        return $pegawai->penempatan()
            ->whereDate('mulai', '<=', Carbon::today())
            ->where(function ($query) {
                $query->whereNull('selesai')
                    ->orWhereDate(
                        'selesai',
                        '>=',
                        Carbon::today()
                    );
            })
            ->whereIn('unit_id', $accessibleUnitIds)
            ->whereHas('unit', function ($query) {
                $query->where('aktif', true);
            })
            ->exists();
    }
}
