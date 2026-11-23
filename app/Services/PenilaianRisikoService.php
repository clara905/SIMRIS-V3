<?php

namespace App\Services;

use App\Models\MatriksRisiko;
use App\Models\PenilaianRisiko;
use App\Models\Risiko;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class PenilaianRisikoService
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService,
        private readonly UnitAccessService $unitAccessService,
        private readonly AuditService $auditService,
    ) {}

    public function create(
        Risiko $risiko,
        User $user,
        array $data,
    ): PenilaianRisiko {
        return DB::transaction(function () use (
            $risiko,
            $user,
            $data,
        ): PenilaianRisiko {
            $lockedRisiko = Risiko::query()
                ->lockForUpdate()
                ->findOrFail($risiko->id);

            $asesmen = $lockedRisiko->asesmen()
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan hanya STAFF yang melakukan penilaian.
            if (
                $this->roleScopeService->getActiveRoleCode($user)
                !== 'STAFF'
            ) {
                throw new LogicException(
                    'Hanya STAFF yang dapat membuat penilaian risiko.'
                );
            }

            // Pastikan asesmen berada dalam cakupan unit pengguna.
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            if (
                $accessibleUnitIds === null
                || ! in_array(
                    (int) $asesmen->unit_id,
                    $accessibleUnitIds,
                    true
                )
            ) {
                throw new LogicException(
                    'Anda tidak memiliki akses ke unit asesmen ini.'
                );
            }

            // Penilaian hanya dapat dilakukan pada asesmen yang masih
            // dikerjakan atau telah dikembalikan untuk diperbaiki.
            if (! in_array($asesmen->status, [
                'DRAFT',
                'DIKEMBALIKAN',
            ], true)) {
                throw new LogicException(
                    'Penilaian tidak dapat ditambahkan karena asesmen '
                    .'sudah diajukan atau diproses.'
                );
            }

            if ($asesmen->versi_metode_id === null) {
                throw new LogicException(
                    'Asesmen belum memiliki versi metode risiko.'
                );
            }

            $versiMetode = $asesmen->versiMetode()
                ->lockForUpdate()
                ->first();

            if (! $versiMetode || ! $versiMetode->dibekukan) {
                throw new LogicException(
                    'Metode risiko harus dibekukan sebelum penilaian.'
                );
            }

            $matriks = MatriksRisiko::query()
                ->with([
                    'kemungkinan',
                    'dampak',
                    'tingkatRisiko',
                ])
                ->lockForUpdate()
                ->findOrFail($data['matriks_id']);

            if (
                (int) $matriks->versi_metode_id
                !== (int) $asesmen->versi_metode_id
            ) {
                throw new LogicException(
                    'Matriks risiko tidak berasal dari versi metode '
                    .'yang digunakan asesmen.'
                );
            }

            if (
                (int) $matriks->kemungkinan->versi_metode_id
                !== (int) $asesmen->versi_metode_id
                || (int) $matriks->dampak->versi_metode_id
                !== (int) $asesmen->versi_metode_id
                || (int) $matriks->tingkatRisiko->versi_metode_id
                !== (int) $asesmen->versi_metode_id
            ) {
                throw new LogicException(
                    'Komponen matriks tidak konsisten dengan versi metode.'
                );
            }

            $penilaian = PenilaianRisiko::create([
                'risiko_id' => $lockedRisiko->id,
                'matriks_id' => $matriks->id,
                'dinilai_oleh' => $user->id,
                'jenis_penilaian' => $data['jenis_penilaian'],
                'alasan_kemungkinan' => $data['alasan_kemungkinan'],
                'alasan_dampak' => $data['alasan_dampak'],
                'kesimpulan' => $data['kesimpulan'],
                'dinilai_pada' => now(),
            ]);

            $this->auditService->record(
                'PENILAIAN_RISIKO_DIBUAT',
                'PENILAIAN_RISIKO',
                $penilaian->id,
                null,
                [
                    'risiko_id' => $lockedRisiko->id,
                    'asesmen_id' => $asesmen->id,
                    'matriks_id' => $matriks->id,
                    'jenis_penilaian' => $penilaian->jenis_penilaian,
                ],
            );

            return $penilaian->load([
                'matriks.kemungkinan',
                'matriks.dampak',
                'matriks.tingkatRisiko',
                'penilai.pegawai',
            ]);
        });
    }
}
