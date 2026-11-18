<?php

namespace App\Services;

use App\Models\PenempatanPegawai;
use App\Models\PengajuanAkun;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Support\Facades\DB;
use LogicException;

class PengajuanAkunService
{
    public function __construct(
        private readonly RoleScopeAssignmentService $roleAssignmentService,
        private readonly AuditService $auditService
    ) {}

    public function approve(
        PengajuanAkun $pengajuan,
        string $roleCode,
        ?int $unitId,
        ?string $catatan = null
    ): PengajuanAkun {
        return DB::transaction(function () use (
            $pengajuan,
            $roleCode,
            $unitId,
            $catatan
        ) {
            $lockedSubmission = PengajuanAkun::query()
                ->whereKey($pengajuan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedSubmission->status
                !== PengajuanAkun::STATUS_MENUNGGU
            ) {
                throw new LogicException(
                    'Pengajuan ini sudah pernah diproses.'
                );
            }

            $user = User::query()
                ->whereKey($lockedSubmission->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->aktif) {
                throw new LogicException(
                    'Akun sudah aktif dan tidak dapat diproses sebagai akun baru.'
                );
            }

            if (! $user->pegawai_id || ! $user->pegawai?->aktif) {
                throw new LogicException(
                    'Akun tidak terhubung dengan pegawai aktif.'
                );
            }

            if (UserRoleScope::query()
                ->where('user_id', $user->id)
                ->exists()) {
                throw new LogicException(
                    'Akun sudah memiliki riwayat penugasan role.'
                );
            }

            $placements = PenempatanPegawai::query()
                ->with('unit')
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
                    'Pegawai harus memiliki tepat satu penempatan aktif.'
                );
            }

            if (! $placements->first()->unit?->aktif) {
                throw new LogicException(
                    'Unit penempatan pegawai tidak aktif.'
                );
            }

            // RoleScopeAssignmentService mensyaratkan user aktif.
            // Perubahan ini masih berada dalam transaksi yang sama.
            $user->update(['aktif' => true]);

            $this->roleAssignmentService->assignRole(
                $user,
                $roleCode,
                $unitId
            );

            $lockedSubmission->update([
                'status' => PengajuanAkun::STATUS_DISETUJUI,
                'catatan' => $catatan,
                'ditinjau_oleh' => auth()->id(),
                'ditinjau_pada' => now(),
            ]);

            $this->auditService->record(
                aksi: 'SETUJUI_PENGAJUAN_AKUN',
                entitas: 'pengajuan_akun',
                entitasId: $lockedSubmission->id,
                dataLama: [
                    'status' => PengajuanAkun::STATUS_MENUNGGU,
                    'user_aktif' => false,
                ],
                dataBaru: [
                    'status' => PengajuanAkun::STATUS_DISETUJUI,
                    'user_aktif' => true,
                    'role_code' => $roleCode,
                    'unit_id' => $unitId,
                ],
                catatan: $catatan
            );

            return $lockedSubmission->fresh([
                'user.pegawai.penempatan.unit',
                'peninjau.pegawai',
            ]);
        });
    }

    public function reject(
        PengajuanAkun $pengajuan,
        string $catatan
    ): PengajuanAkun {
        return DB::transaction(function () use (
            $pengajuan,
            $catatan
        ) {
            $lockedSubmission = PengajuanAkun::query()
                ->whereKey($pengajuan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedSubmission->status
                !== PengajuanAkun::STATUS_MENUNGGU
            ) {
                throw new LogicException(
                    'Pengajuan ini sudah pernah diproses.'
                );
            }

            $user = User::query()
                ->whereKey($lockedSubmission->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($user->aktif) {
                throw new LogicException(
                    'Akun sudah aktif. Pengajuan tidak dapat ditolak sebagai akun baru.'
                );
            }

            if (UserRoleScope::query()
                ->where('user_id', $user->id)
                ->exists()) {
                throw new LogicException(
                    'Akun sudah memiliki riwayat penugasan role.'
                );
            }

            $lockedSubmission->update([
                'status' => PengajuanAkun::STATUS_DITOLAK,
                'catatan' => $catatan,
                'ditinjau_oleh' => auth()->id(),
                'ditinjau_pada' => now(),
            ]);

            $this->auditService->record(
                aksi: 'TOLAK_PENGAJUAN_AKUN',
                entitas: 'pengajuan_akun',
                entitasId: $lockedSubmission->id,
                dataLama: [
                    'status' => PengajuanAkun::STATUS_MENUNGGU,
                    'user_aktif' => false,
                ],
                dataBaru: [
                    'status' => PengajuanAkun::STATUS_DITOLAK,
                    'user_aktif' => false,
                ],
                catatan: $catatan
            );

            return $lockedSubmission->fresh([
                'user.pegawai.penempatan.unit',
                'peninjau.pegawai',
            ]);
        });
    }
}
