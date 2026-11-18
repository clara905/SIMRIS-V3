<?php

namespace App\Services;

use App\Models\PenempatanPegawai;
use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Support\Facades\DB;
use LogicException;

class RoleScopeAssignmentService
{
    /**
     * Memberikan role baru atau mengganti role sebelumnya.
     */
    public function assignRole(
        User $user,
        string $roleCode,
        ?int $unitId = null
    ): UserRoleScope {
        return DB::transaction(function () use ($user, $roleCode, $unitId) {
            // Mengunci user agar proses assignment bersamaan tidak bertabrakan.
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedUser->aktif) {
                throw new LogicException(
                    'User tidak aktif dan tidak dapat diberikan role.'
                );
            }

            if ($lockedUser->pegawai_id !== null) {
                $pegawai = $lockedUser->pegawai;

                if (! $pegawai || ! $pegawai->aktif) {
                    throw new LogicException(
                        'Pegawai tidak aktif dan tidak dapat diberikan role.'
                    );
                }
            }

            $role = Role::query()
                ->where('kode', $roleCode)
                ->first();

            if (! $role) {
                throw new LogicException(
                    'Role tidak ditemukan.'
                );
            }

            $allowedRoles = [
                'SUPER_ADMIN',
                'ADMIN_SATKER',
                'KASATKER',
                'KABID',
                'KASUB',
                'STAFF',
            ];

            if (! in_array($roleCode, $allowedRoles, true)) {
                throw new LogicException(
                    'Kode role tidak diizinkan.'
                );
            }

            $this->validateRoleUnit(
                $lockedUser,
                $roleCode,
                $unitId
            );

            $now = now();

            $existingScopes = UserRoleScope::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('selesai')
                ->lockForUpdate()
                ->get();

            if ($existingScopes->count() > 1) {
                throw new LogicException(
                    'User memiliki lebih dari satu role yang belum berakhir.'
                );
            }

            $existingScope = $existingScopes->first();

            if ($existingScope) {
                if ($existingScope->mulai->gt($now)) {
                    throw new LogicException(
                        'Role sebelumnya memiliki tanggal mulai di masa depan.'
                    );
                }

                // Assignment yang sama tidak perlu dibuat ulang.
                if (
                    $existingScope->role_id === $role->id
                    && $existingScope->unit_id === $unitId
                ) {
                    return $existingScope;
                }

                // Tutup riwayat role sebelumnya.
                $existingScope->update([
                    'selesai' => $now,
                ]);
            }

            return UserRoleScope::create([
                'user_id' => $lockedUser->id,
                'role_id' => $role->id,
                'unit_id' => $unitId,
                'mulai' => $now,
                'selesai' => null,
            ]);
        });
    }

    /**
     * Validasi kesesuaian role dan unit organisasi.
     */
    private function validateRoleUnit(
        User $user,
        string $roleCode,
        ?int $unitId
    ): void {
        if (in_array($roleCode, ['SUPER_ADMIN', 'STAFF'], true)) {
            if ($unitId !== null) {
                throw new LogicException(
                    "{$roleCode} tidak boleh memiliki unit_id."
                );
            }

            if ($roleCode === 'STAFF') {
                $this->validateStaffPlacement($user);
            }

            return;
        }

        if ($unitId === null) {
            throw new LogicException(
                'Role ini wajib memiliki unit organisasi.'
            );
        }

        $expectedUnitTypes = [
            'ADMIN_SATKER' => 'SATKER',
            'KASATKER' => 'SATKER',
            'KABID' => 'BIDANG',
            'KASUB' => 'SUBBIDANG',
        ];

        $unit = UnitOrganisasi::query()
            ->whereKey($unitId)
            ->lockForUpdate()
            ->first();

        if (! $unit) {
            throw new LogicException(
                'Unit organisasi tidak ditemukan.'
            );
        }

        if (! $unit->aktif) {
            throw new LogicException(
                'Unit organisasi tidak aktif.'
            );
        }

        if ($unit->jenis !== $expectedUnitTypes[$roleCode]) {
            throw new LogicException(
                'Jenis unit organisasi tidak sesuai dengan role.'
            );
        }
    }

    /**
     * STAFF wajib memiliki tepat satu penempatan aktif.
     */
    private function validateStaffPlacement(User $user): void
    {
        if (! $user->pegawai_id) {
            throw new LogicException(
                'STAFF wajib terhubung dengan data pegawai.'
            );
        }

        $placements = PenempatanPegawai::query()
            ->with('unit')
            ->where('pegawai_id', $user->pegawai_id)
            ->whereDate('mulai', '<=', today())
            ->whereNull('selesai')
            ->lockForUpdate()
            ->get();

        if ($placements->count() !== 1) {
            throw new LogicException(
                'STAFF wajib memiliki tepat satu penempatan aktif.'
            );
        }

        if (! $placements->first()->unit?->aktif) {
            throw new LogicException(
                'Unit penempatan STAFF tidak aktif.'
            );
        }
    }
}
