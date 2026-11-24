<?php

namespace App\Services;

use App\Models\LogAudit;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\PenugasanAset;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class PenempatanPegawaiService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly RoleScopeService $roleScopeService
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
            $lockedUser = User::query()
                ->where('pegawai_id', $lockedPegawai->id)
                ->lockForUpdate()
                ->first();

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
            $assetAssignmentSnapshots = [];

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
                $assetAssignmentSnapshots[] = $oldAssignmentSnapshot;

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

            $scopeTransition = $this->synchronizeStaffRoleScope(
                $lockedUser,
                $newUnit,
                $tanggalBaru,
                (int) $oldPlacement->unit_id,
                (int) $newPlacement->id
            );

            $newPlacementSnapshot = $this->placementSnapshot($newPlacement);
            $newPlacementSnapshot['transfer_details'] = [
                'tanggal_efektif' => $tanggalBaru->toISOString(),
                'penugasan_aset' => $assetAssignmentSnapshots,
                'role_scope' => $scopeTransition,
            ];

            $this->auditService->record(
                aksi: 'CHANGE_PLACEMENT',
                entitas: 'penempatan_pegawai',
                entitasId: $newPlacement->id,
                dataLama: $oldSnapshot,
                dataBaru: $newPlacementSnapshot,
            );

            return $newPlacement;
        });
    }

    public function cancelScheduledTransfer(Pegawai $pegawai): PenempatanPegawai
    {
        return DB::transaction(function () use ($pegawai): PenempatanPegawai {
            $lockedPegawai = $this->lockActivePegawai($pegawai);
            $lockedUser = User::query()
                ->where('pegawai_id', $lockedPegawai->id)
                ->lockForUpdate()
                ->first();

            $scheduledPlacements = PenempatanPegawai::query()
                ->with(['pegawai', 'unit'])
                ->where('pegawai_id', $lockedPegawai->id)
                ->whereDate('mulai', '>', today())
                ->whereNull('selesai')
                ->lockForUpdate()
                ->get();

            if ($scheduledPlacements->count() !== 1) {
                throw new LogicException(
                    'Pembatalan wajib memiliki tepat satu transfer yang belum efektif.'
                );
            }

            $scheduledPlacement = $scheduledPlacements->first();
            $tanggalEfektif = CarbonImmutable::parse(
                $scheduledPlacement->mulai
            )->startOfDay();
            $tanggalAkhirLama = $tanggalEfektif->subDay()->toDateString();

            $transferAudit = LogAudit::query()
                ->where('aksi', 'CHANGE_PLACEMENT')
                ->where('entitas', 'penempatan_pegawai')
                ->where('entitas_id', $scheduledPlacement->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $transferDetails = $transferAudit?->data_baru['transfer_details'] ?? null;
            $oldPlacementSnapshot = $transferAudit?->data_lama;
            $scheduledPlacementSnapshot = $transferAudit?->data_baru;

            if (
                ! is_array($transferDetails)
                || ! is_array($oldPlacementSnapshot)
                || ! is_array($scheduledPlacementSnapshot)
                || ! isset(
                    $oldPlacementSnapshot['penempatan_id'],
                    $oldPlacementSnapshot['unit_id'],
                    $oldPlacementSnapshot['mulai']
                )
                || ! isset(
                    $scheduledPlacementSnapshot['penempatan_id'],
                    $scheduledPlacementSnapshot['unit_id'],
                    $scheduledPlacementSnapshot['mulai']
                )
                || CarbonImmutable::parse(
                    $transferDetails['tanggal_efektif'] ?? ''
                )->toDateString() !== $tanggalEfektif->toDateString()
                || (int) $scheduledPlacementSnapshot['penempatan_id']
                    !== (int) $scheduledPlacement->id
                || (int) $scheduledPlacementSnapshot['unit_id']
                    !== (int) $scheduledPlacement->unit_id
                || $scheduledPlacementSnapshot['mulai']
                    !== $scheduledPlacement->mulai?->toDateString()
            ) {
                throw new LogicException(
                    'Snapshot audit transfer tidak tersedia atau tidak konsisten; pembatalan otomatis ditolak.'
                );
            }

            $oldPlacement = PenempatanPegawai::query()
                ->with(['pegawai', 'unit'])
                ->whereKey($oldPlacementSnapshot['penempatan_id'])
                ->where('pegawai_id', $lockedPegawai->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $oldPlacement
                || (int) $oldPlacement->unit_id !== (int) $oldPlacementSnapshot['unit_id']
                || $oldPlacement->mulai?->toDateString() !== $oldPlacementSnapshot['mulai']
                || $oldPlacement->selesai?->toDateString() !== $tanggalAkhirLama
                || $scheduledPlacement->unit_id === $oldPlacement->unit_id
            ) {
                throw new LogicException(
                    'Riwayat penempatan telah berubah sejak transfer dijadwalkan; pembatalan otomatis ditolak.'
                );
            }

            $assetSnapshots = $transferDetails['penugasan_aset'] ?? null;

            if (! is_array($assetSnapshots)) {
                throw new LogicException(
                    'Snapshot penugasan aset transfer tidak valid.'
                );
            }

            $destinationAssetAssignmentExists = PenugasanAset::query()
                ->where('pegawai_id', $lockedPegawai->id)
                ->where(function ($query) use ($tanggalEfektif) {
                    $query->whereNull('selesai')
                        ->orWhereDate(
                            'selesai',
                            '>=',
                            $tanggalEfektif->toDateString()
                        );
                })
                ->whereHas('aset', function ($query) use ($scheduledPlacement) {
                    $query->where(
                        'unit_pemilik_id',
                        $scheduledPlacement->unit_id
                    );
                })
                ->lockForUpdate()
                ->exists();

            if ($destinationAssetAssignmentExists) {
                throw new LogicException(
                    'Transfer tidak dapat dibatalkan karena sudah ada penugasan aset pada unit tujuan setelah tanggal efektif.'
                );
            }

            $assetAssignments = [];

            foreach ($assetSnapshots as $assetSnapshot) {
                if (
                    ! is_array($assetSnapshot)
                    || ! isset($assetSnapshot['penugasan_id'])
                    || ($assetSnapshot['selesai'] ?? null) !== null
                ) {
                    throw new LogicException(
                        'Snapshot penugasan aset transfer tidak konsisten.'
                    );
                }

                $assignment = PenugasanAset::query()
                    ->with(['aset', 'pegawai'])
                    ->whereKey($assetSnapshot['penugasan_id'])
                    ->where('pegawai_id', $lockedPegawai->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $assignment
                    || (int) $assignment->aset_idx !== (int) $assetSnapshot['aset_idx']
                    || (int) $assignment->aset?->unit_pemilik_id
                        !== (int) $assetSnapshot['unit_pemilik_id']
                    || (int) $assignment->pegawai_id
                        !== (int) $assetSnapshot['pegawai_id']
                    || $assignment->jenis_tanggung_jawab !== $assetSnapshot['jenis_tanggung_jawab']
                    || $assignment->mulai?->toDateString() !== $assetSnapshot['mulai']
                    || $assignment->selesai?->toDateString() !== $tanggalAkhirLama
                ) {
                    throw new LogicException(
                        'Penugasan aset telah berubah sejak transfer dijadwalkan; pembatalan otomatis ditolak.'
                    );
                }

                $assetAssignments[] = [
                    'model' => $assignment,
                    'snapshot' => $assetSnapshot,
                    'current' => $this->assetAssignmentSnapshot($assignment),
                ];
            }

            $scopeTransition = $transferDetails['role_scope'] ?? null;
            $scopeModels = null;

            if ($scopeTransition !== null) {
                if (
                    ! is_array($scopeTransition)
                    || ($scopeTransition['mode'] ?? null) !== 'split'
                    || ! is_array($scopeTransition['scope_lama'] ?? null)
                    || ! is_array($scopeTransition['scope_baru'] ?? null)
                    || ! isset(
                        $scopeTransition['scope_lama']['scope_id'],
                        $scopeTransition['scope_lama']['unit_id'],
                        $scopeTransition['scope_baru']['scope_id'],
                        $scopeTransition['scope_baru']['unit_id']
                    )
                    || ! $lockedUser
                ) {
                    throw new LogicException(
                        'Snapshot perubahan scope transfer tidak dapat dibatalkan secara otomatis.'
                    );
                }

                $oldScope = UserRoleScope::query()
                    ->with(['role', 'unit'])
                    ->whereKey($scopeTransition['scope_lama_id'] ?? null)
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->first();
                $newScope = UserRoleScope::query()
                    ->with(['role', 'unit'])
                    ->whereKey($scopeTransition['scope_baru_id'] ?? null)
                    ->where('user_id', $lockedUser->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $oldScope
                    || ! $newScope
                    || (int) $scopeTransition['scope_lama']['scope_id']
                        !== (int) $oldScope->id
                    || (int) $scopeTransition['scope_lama']['unit_id']
                        !== (int) $oldPlacement->unit_id
                    || (int) $scopeTransition['scope_baru']['scope_id']
                        !== (int) $newScope->id
                    || (int) $scopeTransition['scope_baru']['unit_id']
                        !== (int) $scheduledPlacement->unit_id
                    || $oldScope->role?->kode !== 'STAFF'
                    || (int) $oldScope->unit_id !== (int) $oldPlacement->unit_id
                    || (int) $newScope->unit_id !== (int) $scheduledPlacement->unit_id
                    || $newScope->role_id !== $oldScope->role_id
                    || $oldScope->selesai?->toISOString() !== $tanggalEfektif->toISOString()
                    || $newScope->mulai?->toISOString() !== $tanggalEfektif->toISOString()
                    || $newScope->selesai !== null
                    || ($scopeTransition['scope_lama']['selesai'] ?? null) !== null
                ) {
                    throw new LogicException(
                        'Scope role telah berubah sejak transfer dijadwalkan; pembatalan otomatis ditolak.'
                    );
                }

                $scopeModels = [
                    'old' => $oldScope,
                    'new' => $newScope,
                    'old_original' => $scopeTransition['scope_lama'],
                ];
            }

            $oldPlacementCurrent = $this->placementSnapshot($oldPlacement);
            $scheduledPlacementCurrent = $this->placementSnapshot(
                $scheduledPlacement
            );
            $scopeCurrent = $scopeModels
                ? [
                    'lama' => $this->roleScopeSnapshot($scopeModels['old']),
                    'baru' => $this->roleScopeSnapshot($scopeModels['new']),
                ]
                : null;
            $assetsCurrent = array_column($assetAssignments, 'current');

            foreach ($assetAssignments as $assetAssignment) {
                /** @var PenugasanAset $assignment */
                $assignment = $assetAssignment['model'];
                $assignment->selesai = $assetAssignment['snapshot']['selesai'];
                $assignment->save();
                $assignment->refresh();
                $assignment->load(['aset', 'pegawai']);

                $this->auditService->record(
                    aksi: 'RESTORE_ASSET_ASSIGNMENT',
                    entitas: 'penugasan_aset',
                    entitasId: $assignment->id,
                    dataLama: $assetAssignment['current'],
                    dataBaru: $this->assetAssignmentSnapshot($assignment),
                    catatan: 'Penugasan aset dipulihkan karena transfer pegawai terjadwal dibatalkan.',
                );
            }

            if ($scopeModels) {
                $oldScope = $scopeModels['old'];
                $newScope = $scopeModels['new'];
                $oldScope->selesai = $scopeModels['old_original']['selesai'];
                $oldScope->save();
                $newScope->delete();

                $oldScope->refresh();
                $oldScope->load(['role', 'unit']);

                $this->auditService->record(
                    aksi: 'CANCEL_ROLE_SCOPE_TRANSFER',
                    entitas: 'user_role_scope',
                    entitasId: $newScope->id,
                    dataLama: [
                        'scope_lama' => $scopeCurrent['lama'],
                        'scope_baru' => $scopeCurrent['baru'],
                    ],
                    dataBaru: [
                        'scope_lama' => $this->roleScopeSnapshot($oldScope),
                        'scope_baru' => null,
                        'tanggal_efektif' => $tanggalEfektif->toISOString(),
                    ],
                    catatan: 'Scope tujuan dibatalkan dan scope asal dipulihkan.',
                );
            }

            $oldPlacement->update([
                'selesai' => $oldPlacementSnapshot['selesai'],
            ]);
            $scheduledPlacement->delete();

            $oldPlacement->refresh();
            $oldPlacement->load(['pegawai', 'unit']);

            $this->auditService->record(
                aksi: 'CANCEL_SCHEDULED_TRANSFER',
                entitas: 'penempatan_pegawai',
                entitasId: $scheduledPlacement->id,
                dataLama: [
                    'penempatan_lama' => $oldPlacementCurrent,
                    'penempatan_terjadwal' => $scheduledPlacementCurrent,
                    'scope' => $scopeCurrent,
                    'penugasan_aset' => $assetsCurrent,
                ],
                dataBaru: [
                    'status' => 'DIBATALKAN',
                    'tanggal_efektif' => $tanggalEfektif->toISOString(),
                    'penempatan_lama' => $this->placementSnapshot($oldPlacement),
                    'penempatan_terjadwal' => null,
                    'scope_lama' => $scopeModels
                        ? $this->roleScopeSnapshot($scopeModels['old'])
                        : null,
                    'scope_baru' => null,
                    'penugasan_aset' => array_map(
                        fn (array $assetAssignment): array => $this->assetAssignmentSnapshot(
                            $assetAssignment['model']
                        ),
                        $assetAssignments
                    ),
                ],
                catatan: 'Transfer pegawai dibatalkan sebelum tanggal efektif.',
            );

            return $oldPlacement;
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

    private function synchronizeStaffRoleScope(
        ?User $user,
        UnitOrganisasi $unitTujuan,
        CarbonImmutable $tanggalEfektif,
        int $unitAsalId,
        int $transferPlacementId
    ): ?array {
        if (! $user) {
            return null;
        }

        $activeScope = $this->roleScopeService->getActiveScope($user);

        if ($activeScope?->role?->kode !== 'STAFF') {
            return null;
        }

        $scope = UserRoleScope::query()
            ->whereKey($activeScope->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ((int) $scope->unit_id !== $unitAsalId) {
            throw new LogicException(
                'Scope STAFF tidak sesuai dengan unit penempatan asal.'
            );
        }

        $now = now();
        $hasFutureScope = UserRoleScope::query()
            ->where('user_id', $user->id)
            ->where('mulai', '>', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('selesai')
                    ->orWhere('selesai', '>', $now);
            })
            ->exists();

        if ($hasFutureScope) {
            throw new LogicException(
                'Transfer tidak dapat dijadwalkan karena pengguna sudah memiliki perubahan scope mendatang.'
            );
        }

        $oldSnapshot = $this->roleScopeSnapshot($scope);
        $tanggalEfektif = $tanggalEfektif->startOfDay();
        $transitionMode = 'split';

        if ($scope->mulai->greaterThan($tanggalEfektif)) {
            $transitionMode = 'update';
            $scope->update([
                'unit_id' => $unitTujuan->id,
            ]);

            $scope->refresh();
            $scope->load(['role', 'unit']);

            $newSnapshot = $this->roleScopeSnapshot($scope);
            $transition = [
                'mode' => $transitionMode,
                'scope_lama_id' => $scope->id,
                'scope_baru_id' => $scope->id,
                'scope_lama' => $oldSnapshot,
                'scope_baru' => $newSnapshot,
                'tanggal_efektif' => $tanggalEfektif->toISOString(),
                'transfer_penempatan_id' => $transferPlacementId,
            ];
        } else {
            $scope->update([
                'selesai' => $tanggalEfektif,
            ]);
            $scope->refresh();
            $scope->load(['role', 'unit']);

            $newScope = UserRoleScope::create([
                'user_id' => $scope->user_id,
                'role_id' => $scope->role_id,
                'unit_id' => $unitTujuan->id,
                'mulai' => $tanggalEfektif,
                'selesai' => null,
            ]);
            $newScope->load(['role', 'unit']);

            $transition = [
                'mode' => $transitionMode,
                'scope_lama_id' => $scope->id,
                'scope_baru_id' => $newScope->id,
                'scope_lama' => $oldSnapshot,
                'scope_lama_setelah' => $this->roleScopeSnapshot($scope),
                'scope_baru' => $this->roleScopeSnapshot($newScope),
                'tanggal_efektif' => $tanggalEfektif->toISOString(),
                'transfer_penempatan_id' => $transferPlacementId,
            ];
        }

        $this->auditService->record(
            aksi: 'CHANGE_ROLE',
            entitas: 'user_role_scope',
            entitasId: $transition['scope_baru_id'],
            dataLama: $oldSnapshot,
            dataBaru: $transition['scope_baru'] + [
                'tanggal_efektif' => $tanggalEfektif->toISOString(),
                'transfer_penempatan_id' => $transferPlacementId,
            ],
            catatan: 'Scope role STAFF diselaraskan dengan tanggal efektif perpindahan pegawai.',
        );

        return $transition;
    }

    private function roleScopeSnapshot(
        UserRoleScope $scope
    ): array {
        $scope->loadMissing(['role', 'unit']);

        return [
            'scope_id' => $scope->id,
            'user_id' => $scope->user_id,
            'role_id' => $scope->role_id,
            'role_kode' => $scope->role?->kode,
            'role_nama' => $scope->role?->nama,
            'unit_id' => $scope->unit_id,
            'unit_kode' => $scope->unit?->kode,
            'unit_nama' => $scope->unit?->nama,
            'mulai' => $scope->mulai?->toISOString(),
            'selesai' => $scope->selesai?->toISOString(),
        ];
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
