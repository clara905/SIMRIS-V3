<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PenugasanAset;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenugasanAsetService
{
    public function __construct(
        private readonly AsetAccessService $asetAccessService,
        private readonly AuditService $auditService
    ) {}

    public function visibleAssignments(
        Aset $aset,
        User $user
    ): HasMany {
        if (! $this->asetAccessService->canViewAsset($user, $aset)) {
            abort(403, 'Anda tidak memiliki akses ke riwayat aset ini.');
        }

        return $aset->penugasan()
            ->with('pegawai')
            ->orderByDesc('mulai')
            ->orderByDesc('id');
    }

    public function create(
        Aset $aset,
        User $user,
        array $data
    ): PenugasanAset {
        if (! $this->asetAccessService->canManageAsset($user, $aset)) {
            abort(403, 'Anda tidak berwenang mengelola penugasan aset ini.');
        }

        return DB::transaction(function () use ($aset, $data) {
            $lockedAsset = Aset::query()
                ->whereKey($aset->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $pegawai = Pegawai::query()
                ->whereKey($data['pegawai_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $pegawai->aktif) {
                throw ValidationException::withMessages([
                    'pegawai_id' => 'Pegawai tidak aktif.',
                ]);
            }

            $mulai = $data['mulai'];
            $selesai = $data['selesai'] ?? null;

            $this->validatePlacementPeriod(
                $pegawai,
                (int) $lockedAsset->unit_pemilik_id,
                $mulai,
                $selesai
            );

            $this->validateNoOverlap(
                $lockedAsset,
                $data['jenis_tanggung_jawab'],
                $mulai,
                $selesai
            );

            $assignment = PenugasanAset::query()->create([
                'aset_idx' => $lockedAsset->idx,
                'pegawai_id' => $pegawai->id,
                'jenis_tanggung_jawab' => $data['jenis_tanggung_jawab'],
                'mulai' => $mulai,
                'selesai' => $selesai,
            ]);

            $this->auditService->record(
                'CREATE_PENUGASAN_ASET',
                'penugasan_aset',
                (int) $assignment->id,
                null,
                $this->assignmentSnapshot($assignment),
                'Penugasan aset dibuat.'
            );

            return $assignment->load('pegawai');
        });
    }

    public function end(
        PenugasanAset $penugasan,
        User $user,
        string $tanggalSelesai
    ): PenugasanAset {
        $penugasan->loadMissing('aset');

        if (
            ! $penugasan->aset ||
            ! $this->asetAccessService->canManageAsset(
                $user,
                $penugasan->aset
            )
        ) {
            abort(403, 'Anda tidak berwenang mengakhiri penugasan ini.');
        }

        return DB::transaction(function () use (
            $penugasan,
            $tanggalSelesai
        ) {
            $assignment = PenugasanAset::query()
                ->whereKey($penugasan->id)
                ->lockForUpdate()
                ->with(['aset', 'pegawai'])
                ->firstOrFail();

            if (
                $assignment->selesai !== null
                && $assignment->selesai->lt(today())
            ) {
                throw ValidationException::withMessages([
                    'tanggal_selesai' => 'Penugasan ini sudah berakhir.',
                ]);
            }

            if ($tanggalSelesai < $assignment->mulai->toDateString()) {
                throw ValidationException::withMessages([
                    'tanggal_selesai' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
                ]);
            }

            $this->validatePlacementPeriod(
                $assignment->pegawai,
                (int) $assignment->aset->unit_pemilik_id,
                $assignment->mulai->toDateString(),
                $tanggalSelesai
            );

            $this->validateNoOverlap(
                $assignment->aset,
                $assignment->jenis_tanggung_jawab,
                $assignment->mulai->toDateString(),
                $tanggalSelesai,
                (int) $assignment->id
            );

            $dataLama = $this->assignmentSnapshot($assignment);

            $assignment->selesai = $tanggalSelesai;
            $assignment->save();

            $this->auditService->record(
                'END_PENUGASAN_ASET',
                'penugasan_aset',
                (int) $assignment->id,
                $dataLama,
                $this->assignmentSnapshot($assignment),
                $dataLama['selesai'] !== null
                    ? 'Jadwal akhir penugasan aset diperbarui.'
                    : 'Penugasan aset diakhiri.'
            );

            return $assignment->fresh('pegawai');
        });
    }

    private function validatePlacementPeriod(
        Pegawai $pegawai,
        int $unitId,
        string $mulai,
        ?string $selesai
    ): void {
        $placementQuery = PenempatanPegawai::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('unit_id', $unitId)
            ->whereDate('mulai', '<=', $mulai);

        if ($selesai === null) {
            $placementQuery->whereNull('selesai');
        } else {
            $placementQuery->where(function ($query) use ($selesai) {
                $query->whereNull('selesai')
                    ->orWhereDate('selesai', '>=', $selesai);
            });
        }

        if (! $placementQuery->exists()) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Seluruh periode penugasan harus tercakup dalam satu periode penempatan pegawai pada unit pemilik aset.',
            ]);
        }
    }

    private function validateNoOverlap(
        Aset $aset,
        string $jenis,
        string $mulai,
        ?string $selesai,
        ?int $ignoreAssignmentId = null
    ): void {
        $query = PenugasanAset::query()
            ->where('aset_idx', $aset->idx)
            ->where('jenis_tanggung_jawab', $jenis)
            ->whereDate('mulai', '<=', $selesai ?? '9999-12-31')
            ->where(function ($builder) use ($mulai) {
                $builder->whereNull('selesai')
                    ->orWhereDate('selesai', '>=', $mulai);
            })
            ->when(
                $ignoreAssignmentId !== null,
                fn ($builder) => $builder->where(
                    'id',
                    '!=',
                    $ignoreAssignmentId
                )
            );

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'jenis_tanggung_jawab' => 'Sudah ada penugasan dengan jenis tanggung jawab yang sama pada periode tersebut.',
            ]);
        }
    }

    private function assignmentSnapshot(
        PenugasanAset $assignment
    ): array {
        return [
            'id' => $assignment->id,
            'aset_idx' => $assignment->aset_idx,
            'pegawai_id' => $assignment->pegawai_id,
            'jenis_tanggung_jawab' => $assignment->jenis_tanggung_jawab,
            'mulai' => $assignment->mulai?->toDateString(),
            'selesai' => $assignment->selesai?->toDateString(),
        ];
    }
}
