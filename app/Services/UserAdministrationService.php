<?php

namespace App\Services;

use App\Models\PenempatanPegawai;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Support\Facades\DB;
use LogicException;

class UserAdministrationService
{
    private const ALLOWED_ROLES = [
        'SUPER_ADMIN',
        'ADMIN_SATKER',
        'KASATKER',
        'KABID',
        'KASUB',
        'STAFF',
    ];

    private const EXPECTED_UNIT_TYPES = [
        'ADMIN_SATKER' => 'SATKER',
        'KASATKER' => 'SATKER',
        'KABID' => 'BIDANG',
        'KASUB' => 'SUBBIDANG',
    ];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly RoleScopeService $roleScopeService,
    ) {}

    /**
     * Menonaktifkan akun dan mencabut seluruh token Sanctum.
     */
    public function deactivate(
        User $user,
        ?string $catatan = null,
    ): User {
        return DB::transaction(function () use (
            $user,
            $catatan,
        ): User {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedUser->aktif) {
                throw new LogicException(
                    'Akun sudah dalam keadaan nonaktif.'
                );
            }

            // Akun yang hanya menunggu persetujuan tidak boleh
            // dinonaktifkan melalui administrasi akun umum.
            $this->validateAssignedRoleForDeactivation($lockedUser);

            $oldSnapshot = $this->userSnapshot($lockedUser);

            $lockedUser->update([
                'aktif' => false,
            ]);

            // Cabut seluruh token Sanctum milik akun.
            $lockedUser->tokens()->delete();

            $this->auditService->record(
                aksi: 'DEACTIVATE_USER',
                entitas: 'users',
                entitasId: $lockedUser->id,
                dataLama: $oldSnapshot,
                dataBaru: $this->userSnapshot($lockedUser),
                catatan: $catatan,
            );

            return $lockedUser;
        });
    }

    /**
     * Mengaktifkan kembali akun yang sebelumnya sudah memiliki role.
     */
    public function reactivate(
        User $user,
        ?string $catatan = null,
    ): User {
        return DB::transaction(function () use (
            $user,
            $catatan,
        ): User {
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->aktif) {
                throw new LogicException(
                    'Akun sudah dalam keadaan aktif.'
                );
            }

            $oldSnapshot = $this->userSnapshot($lockedUser);

            // Validasi pegawai sebelum status akun diaktifkan.
            $this->validateEmployee($lockedUser);

            // Pastikan terdapat tepat satu scope yang belum berakhir.
            $scopes = UserRoleScope::query()
                ->with(['role', 'unit'])
                ->where('user_id', $lockedUser->id)
                ->whereNull('selesai')
                ->lockForUpdate()
                ->get();

            if ($scopes->count() !== 1) {
                throw new LogicException(
                    'Aktivasi kembali membutuhkan tepat satu role yang belum berakhir.'
                );
            }

            $scope = $scopes->first();

            if ($scope->mulai->gt(now())) {
                throw new LogicException(
                    'Role akun belum berlaku karena tanggal mulai masih di masa depan.'
                );
            }

            $roleCode = $scope->role?->kode;

            if (
                ! $roleCode
                || ! in_array($roleCode, self::ALLOWED_ROLES, true)
            ) {
                throw new LogicException(
                    'Role akun tidak valid atau tidak diizinkan.'
                );
            }

            // Aktifkan sementara di dalam transaksi agar RoleScopeService
            // dapat melakukan validasi menggunakan aturan yang berlaku.
            $lockedUser->update([
                'aktif' => true,
            ]);

            $this->validateRoleAndUnit(
                $lockedUser,
                $scope,
                $roleCode,
            );

            $this->auditService->record(
                aksi: 'REACTIVATE_USER',
                entitas: 'users',
                entitasId: $lockedUser->id,
                dataLama: $oldSnapshot,
                dataBaru: $this->userSnapshot($lockedUser),
                catatan: $catatan,
            );

            return $lockedUser;
        });
    }

    /**
     * Memastikan akun mempunyai tepat satu role yang sudah berlaku.
     */
    private function validateAssignedRoleForDeactivation(
        User $user,
    ): void {
        $scopes = UserRoleScope::query()
            ->with('role')
            ->where('user_id', $user->id)
            ->whereNull('selesai')
            ->lockForUpdate()
            ->get();

        if ($scopes->count() !== 1) {
            throw new LogicException(
                'Penonaktifan membutuhkan tepat satu role aktif.'
            );
        }

        $scope = $scopes->first();

        if ($scope->mulai->gt(now())) {
            throw new LogicException(
                'Role akun belum berlaku karena tanggal mulai masih di masa depan.'
            );
        }

        $roleCode = $scope->role?->kode;

        if (
            ! $roleCode
            || ! in_array($roleCode, self::ALLOWED_ROLES, true)
        ) {
            throw new LogicException(
                'Role akun tidak valid atau tidak diizinkan.'
            );
        }
    }

    /**
     * Pegawai yang terhubung dengan akun wajib masih aktif.
     */
    private function validateEmployee(User $user): void
    {
        if ($user->pegawai_id === null) {
            return;
        }

        $pegawai = $user->pegawai()
            ->lockForUpdate()
            ->first();

        if (! $pegawai || ! $pegawai->aktif) {
            throw new LogicException(
                'Pegawai tidak ditemukan atau sudah nonaktif.'
            );
        }
    }

    /**
     * Memvalidasi role, scope, dan unit organisasi.
     */
    private function validateRoleAndUnit(
        User $user,
        UserRoleScope $scope,
        string $roleCode,
    ): void {
        $activeScope = $this->roleScopeService
            ->getActiveScope($user);

        if (
            ! $activeScope
            || $activeScope->id !== $scope->id
        ) {
            throw new LogicException(
                'Role aktif akun tidak valid.'
            );
        }

        if ($roleCode === 'SUPER_ADMIN') {
            if (! $this->roleScopeService->hasGlobalAccess($user)) {
                throw new LogicException(
                    'Scope SUPER_ADMIN tidak valid.'
                );
            }

            return;
        }

        if ($roleCode === 'STAFF') {
            if ($scope->unit_id === null) {
                throw new LogicException(
                    'STAFF wajib memiliki unit_id pada role scope.'
                );
            }

            if ($user->pegawai_id === null) {
                throw new LogicException(
                    'STAFF wajib terhubung dengan data pegawai.'
                );
            }

            $placements = PenempatanPegawai::query()
                ->where('pegawai_id', $user->pegawai_id)
                ->whereDate('mulai', '<=', today())
                ->where(function ($query) {
                    $query->whereNull('selesai')
                        ->orWhereDate('selesai', '>=', today());
                })
                ->lockForUpdate()
                ->get();

            if ($placements->count() !== 1) {
                throw new LogicException(
                    'STAFF wajib memiliki tepat satu penempatan yang berlaku.'
                );
            }

            return;
        }

        if ($scope->unit_id === null) {
            throw new LogicException(
                'Role ini wajib memiliki unit organisasi.'
            );
        }

        $unit = UnitOrganisasi::query()
            ->whereKey($scope->unit_id)
            ->lockForUpdate()
            ->first();

        if (! $unit || ! $unit->aktif) {
            throw new LogicException(
                'Unit organisasi tidak ditemukan atau nonaktif.'
            );
        }

        $expectedType = self::EXPECTED_UNIT_TYPES[$roleCode] ?? null;

        if ($unit->jenis !== $expectedType) {
            throw new LogicException(
                'Jenis unit organisasi tidak sesuai dengan role.'
            );
        }

        if (
            $this->roleScopeService
                ->getEffectiveUnitId($user) === null
        ) {
            throw new LogicException(
                'Unit akses efektif akun tidak valid.'
            );
        }
    }

    /**
     * Snapshot akun yang aman untuk dicatat dalam audit.
     *
     * Tidak menyertakan firebase_uid, password, atau token.
     */
    private function userSnapshot(User $user): array
    {
        return [
            'user_id' => $user->id,
            'pegawai_id' => $user->pegawai_id,
            'username' => $user->username,
            'email' => $user->email,
            'aktif' => $user->aktif,
        ];
    }
}
