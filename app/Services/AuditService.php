<?php

namespace App\Services;

use App\Models\LogAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class AuditService
{
    public function record(
        string $aksi,
        string $entitas,
        ?int $entitasId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
        ?string $catatan = null,
    ): LogAudit {
        $aksi = trim($aksi);
        $entitas = trim($entitas);

        if ($aksi === '' || mb_strlen($aksi) > 100) {
            throw new InvalidArgumentException(
                'Aksi audit wajib diisi dan maksimal 100 karakter.'
            );
        }

        if ($entitas === '' || mb_strlen($entitas) > 100) {
            throw new InvalidArgumentException(
                'Entitas audit wajib diisi dan maksimal 100 karakter.'
            );
        }

        $user = Auth::user();
        $actor = $user instanceof User ? $user : null;

        return LogAudit::create([
            'user_id' => $actor?->id,
            'pelaku_nama' => $actor
                ? ($actor->pegawai?->nama ?? $actor->username)
                : null,
            'pelaku_username' => $actor?->username,
            'aksi' => $aksi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'data_lama' => $dataLama,
            'data_baru' => $dataBaru,
            'ip_address' => app()->runningInConsole()
                ? null
                : request()->ip(),
            'koreksi_dari_id' => null,
            'catatan' => $catatan,
            'waktu' => now(),
        ]);
    }

    public function correct(
        LogAudit $original,
        string $catatan,
        ?array $dataBaru = null,
    ): LogAudit {
        $catatan = trim($catatan);

        if ($catatan === '') {
            throw new InvalidArgumentException(
                'Catatan koreksi wajib diisi.'
            );
        }

        return DB::transaction(function () use (
            $original,
            $catatan,
            $dataBaru,
        ): LogAudit {
            $lockedOriginal = LogAudit::query()
                ->lockForUpdate()
                ->findOrFail($original->getKey());

            Gate::authorize('correct', $lockedOriginal);

            if ($lockedOriginal->koreksi_dari_id !== null) {
                throw new InvalidArgumentException(
                    'Log koreksi tidak dapat dikoreksi kembali.'
                );
            }

            $user = Auth::user();
            $actor = $user instanceof User ? $user : null;

            return LogAudit::create([
                'user_id' => $actor?->id,
                'pelaku_nama' => $actor
                    ? ($actor->pegawai?->nama ?? $actor->username)
                    : null,
                'pelaku_username' => $actor?->username,
                'aksi' => 'KOREKSI_AUDIT',
                'entitas' => $lockedOriginal->entitas,
                'entitas_id' => $lockedOriginal->entitas_id,
                'data_lama' => null,
                'data_baru' => $dataBaru,
                'ip_address' => app()->runningInConsole()
                    ? null
                    : request()->ip(),
                'koreksi_dari_id' => $lockedOriginal->id,
                'catatan' => $catatan,
                'waktu' => now(),
            ]);
        });
    }
}
