<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
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

    /**
     * Membuat penempatan pertama pegawai.
     */
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
     * Penempatan baru dimulai pada tanggal efektif.
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

    /**
     * Mengakhiri penempatan tanpa membuat penempatan baru.
     */
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

    /**
     * Mengunci dan memvalidasi pegawai.
     */
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

    /**
     * Mengunci dan memvalidasi unit tujuan.
     */
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

    /**
     * Menormalisasi input tanggal menjadi tanggal tanpa komponen waktu.
     */
    private function normalizeDate(
        string|DateTimeInterface $tanggal
    ): CarbonImmutable {
        return CarbonImmutable::parse($tanggal)->startOfDay();
    }

    /**
     * Snapshot penempatan untuk audit.
     */
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
}
