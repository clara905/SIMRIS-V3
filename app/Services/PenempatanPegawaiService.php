<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PenugasanAset;
use App\Models\UnitOrganisasi;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class PenempatanPegawaiService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function assignInitial(
        Pegawai $pegawai,
        int $unitId,
        string|DateTimeInterface $tanggalMulai
    ): PenempatanPegawai {
        return DB::transaction(function () use (
            $pegawai,
            $unitId,
            $tanggalMulai
        ): PenempatanPegawai {
            $lockedPegawai = $this->lockActivePegawai($pegawai);

            $existingPlacements = PenempatanPegawai::query()
                ->where('pegawai_id', $lockedPegawai->id)
                ->lockForUpdate()
                ->exists();

            if ($existingPlacements) {
                throw new LogicException(
                    'Pegawai sudah memiliki riwayat penempatan.'
                );
            }

            $tanggal = $this->normalizeDate($tanggalMulai);
            $unit = $this->lockActiveUnit($unitId);

            $placement = PenempatanPegawai::create([
                'pegawai_id' => $lockedPegawai->id,
                'unit_id' => $unit->id,
                'mulai' => $tanggal,
                'selesai' => null,
            ]);

            $placement->load(['pegawai', 'unit']);

            $this->auditService->record(
                aksi: 'ASSIGN_PLACEMENT',
                entitas: 'penempatan_pegawai',
                entitasId: $placement->id,
                dataLama: null,
                dataBaru: $this->placementSnapshot($placement),
            );

            return $placement;
        });
    }

    /**
     * Memindahkan pegawai ke unit baru.
     *
     * Penempatan lama berakhir sehari sebelum tanggal efektif.
     * Penugasan aset aktif pada unit lama juga otomatis berakhir
     * sehari sebelum tanggal efektif.
     *
     * Semua perubahan dan audit berada dalam satu transaksi.
     */
    public function transfer(
        Pegawai $pegawai,
        int $unitTujuanId,
        string|DateTimeInterface $tanggalEfektif
    ): PenempatanPegawai {
        return DB::transaction(function () use (
            $pegawai,
            $unitTujuanId,
            $tanggalEfektif
        ): PenempatanPegawai {
            $lockedPegawai = $this->lockActivePegawai($pegawai);

            $tanggalBaru = $this->normalizeDate($tanggalEfektif);
            $tanggalAkhirLama = $tanggalBaru->subDay();

            $placements = PenempatanPegawai::query()
                ->with(['pegawai', 'unit'])
                ->where('pegawai_id', $lockedPegawai->id)
                ->whereNull('selesai')
                ->lockForUpdate()
                ->get();

            if ($placements->count() !== 1) {
                throw new LogicException(
                    'Transfer wajib memiliki tepat satu penempatan yang belum berakhir.'
                );
            }

            $oldPlacement = $placements->first();

            if ($tanggalBaru->lessThanOrEqualTo(
                CarbonImmutable::parse($oldPlacement->mulai)
            )) {
                throw new LogicException(
                    'Tanggal perpindahan harus setelah tanggal mulai penempatan lama.'
                );
            }

            $newUnit = $this->lockActiveUnit($unitTujuanId);

            if ((int) $oldPlacement->unit_id === (int) $newUnit->id) {
                throw new LogicException(
                    'Unit tujuan sama dengan unit penempatan sebelumnya.'
                );
            }

            // Pastikan tidak ada riwayat lain yang bertumpang tindih
            // dengan tanggal mulai penempatan baru.
            $overlappingPlacement = PenempatanPegawai::query()
                ->where('pegawai_id', $lockedPegawai->id)
                ->where('id', '!=', $oldPlacement->id)
                ->where(function ($query) use ($tanggalBaru) {
                    $query->whereNull('selesai')
                        ->orWhereDate(
                            'selesai',
                            '>=',
                            $tanggalBaru->toDateString()
                        );
                })
                ->exists();

            if ($overlappingPlacement) {
                throw new LogicException(
                    'Tanggal penempatan baru bertumpang tindih dengan riwayat penempatan lain.'
                );
            }

            // Kunci dan ambil penugasan aset aktif pada unit pemilik lama.
            $activeAssignments = PenugasanAset::query()
                ->with('aset')
                ->where('pegawai_id', $lockedPegawai->id)
                ->whereNull('selesai')
                ->whereHas('aset', function ($query) use ($oldPlacement) {
                    $query->where(
                        'unit_pemilik_id',
                        $oldPlacement->unit_id
                    );
                })
                ->lockForUpdate()
                ->get();

            // Jangan memotong penugasan yang baru dimulai pada atau
            // setelah tanggal efektif perpindahan.
            $futureAssignment = $activeAssignments->first(
                fn (PenugasanAset $assignment) => $assignment->mulai->toDateString()
                    >= $tanggalBaru->toDateString()
            );

            if ($futureAssignment) {
                throw new LogicException(
                    'Mutasi tidak dapat dilakukan karena terdapat penugasan aset yang baru dimulai pada atau setelah tanggal efektif. Selesaikan atau koreksi penugasan tersebut terlebih dahulu.'
                );
            }

            // Tutup penugasan aset aktif pada unit lama dan catat audit.
            foreach ($activeAssignments as $assignment) {
                if (
                    $tanggalAkhirLama->lessThan(
                        CarbonImmutable::parse($assignment->mulai)
                    )
                ) {
                    throw new LogicException(
                        'Tanggal efektif perpindahan menyebabkan periode penugasan aset tidak valid.'
                    );
                }

                $oldAssignmentSnapshot = $this->assetAssignmentSnapshot(
                    $assignment
                );

                $assignment->selesai = $tanggalAkhirLama->toDateString();
                $assignment->save();

                $this->auditService->record(
                    aksi: 'END_ASSET_ASSIGNMENT',
                    entitas: 'penugasan_aset',
                    entitasId: $assignment->id,
                    dataLama: $oldAssignmentSnapshot,
                    dataBaru: $this->assetAssignmentSnapshot($assignment),
                    catatan: sprintf(
                        'Penugasan aset otomatis diakhiri karena mutasi pegawai efektif %s.',
                        $tanggalBaru->toDateString()
                    ),
                );
            }

            $oldSnapshot = $this->placementSnapshot($oldPlacement);

            $oldPlacement->update([
                'selesai' => $tanggalAkhirLama->toDateString(),
            ]);

            $newPlacement = PenempatanPegawai::create([
                'pegawai_id' => $lockedPegawai->id,
                'unit_id' => $newUnit->id,
                'mulai' => $tanggalBaru->toDateString(),
                'selesai' => null,
            ]);

            $newPlacement->load(['pegawai', 'unit']);

            $this->auditService->record(
                aksi: 'CHANGE_PLACEMENT',
                entitas: 'penempatan_pegawai',
                entitasId: $newPlacement->id,
                dataLama: $oldSnapshot,
                dataBaru: $this->placementSnapshot($newPlacement),
            );

            return $newPlacement;
        });
    }

    public function close(
        Pegawai $pegawai,
        string|DateTimeInterface $tanggalSelesai
    ): PenempatanPegawai {
        return DB::transaction(function () use (
            $pegawai,
            $tanggalSelesai
        ): PenempatanPegawai {
            $lockedPegawai = $this->lockActivePegawai($pegawai);

            $tanggal = $this->normalizeDate($tanggalSelesai);

            $placements = PenempatanPegawai::query()
                ->with(['pegawai', 'unit'])
                ->where('pegawai_id', $lockedPegawai->id)
                ->whereNull('selesai')
                ->lockForUpdate()
                ->get();

            if ($placements->count() !== 1) {
                throw new LogicException(
                    'Pengakhiran wajib memiliki tepat satu penempatan yang belum berakhir.'
                );
            }

            $placement = $placements->first();

            if ($tanggal->lessThan(
                CarbonImmutable::parse($placement->mulai)
            )) {
                throw new LogicException(
                    'Tanggal selesai tidak boleh sebelum tanggal mulai penempatan.'
                );
            }

            $oldSnapshot = $this->placementSnapshot($placement);

            $placement->update([
                'selesai' => $tanggal->toDateString(),
            ]);

            $placement->refresh();
            $placement->load(['pegawai', 'unit']);

            $this->auditService->record(
                aksi: 'CLOSE_PLACEMENT',
                entitas: 'penempatan_pegawai',
                entitasId: $placement->id,
                dataLama: $oldSnapshot,
                dataBaru: $this->placementSnapshot($placement),
            );

            return $placement;
        });
    }

    private function lockActivePegawai(Pegawai $pegawai): Pegawai
    {
        $lockedPegawai = Pegawai::query()
            ->lockForUpdate()
            ->findOrFail($pegawai->id);

        if (! $lockedPegawai->aktif) {
            throw new LogicException(
                'Pegawai tidak aktif dan tidak dapat diproses.'
            );
        }

        return $lockedPegawai;
    }

    private function lockActiveUnit(int $unitId): UnitOrganisasi
    {
        $unit = UnitOrganisasi::query()
            ->lockForUpdate()
            ->find($unitId);

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

        return $unit;
    }

    private function normalizeDate(
        string|DateTimeInterface $tanggal
    ): CarbonImmutable {
        return CarbonImmutable::parse($tanggal)->startOfDay();
    }

    private function placementSnapshot(
        PenempatanPegawai $placement
    ): array {
        $placement->loadMissing(['pegawai', 'unit']);

        return [
            'penempatan_id' => $placement->id,
            'pegawai_id' => $placement->pegawai_id,
            'pegawai_nip' => $placement->pegawai?->nip,
            'pegawai_nama' => $placement->pegawai?->nama,
            'unit_id' => $placement->unit_id,
            'unit_kode' => $placement->unit?->kode,
            'unit_nama' => $placement->unit?->nama,
            'mulai' => $placement->mulai?->toDateString(),
            'selesai' => $placement->selesai?->toDateString(),
        ];
    }

    private function assetAssignmentSnapshot(
        PenugasanAset $assignment
    ): array {
        $assignment->loadMissing(['aset', 'pegawai']);

        return [
            'penugasan_id' => $assignment->id,
            'aset_idx' => $assignment->aset_idx,
            'kode_aset' => $assignment->aset?->kode_aset,
            'nama_aset' => $assignment->aset?->nama,
            'unit_pemilik_id' => $assignment->aset?->unit_pemilik_id,
            'pegawai_id' => $assignment->pegawai_id,
            'pegawai_nip' => $assignment->pegawai?->nip,
            'pegawai_nama' => $assignment->pegawai?->nama,
            'jenis_tanggung_jawab' => $assignment->jenis_tanggung_jawab,
            'mulai' => $assignment->mulai?->toDateString(),
            'selesai' => $assignment->selesai?->toDateString(),
        ];
    }
}
