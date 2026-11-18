<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;
use App\Services\UnitAccessService;
use Illuminate\Support\Carbon;
use LogicException;

class PegawaiPolicy
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Menentukan apakah user boleh melihat daftar pegawai.
     */
    public function viewAny(User $user): bool
    {
        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            return $accessibleUnitIds === null
                || $accessibleUnitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Menentukan apakah user boleh melihat data pegawai tertentu.
     */
    public function view(User $user, Pegawai $pegawai): bool
    {
        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            // SUPER_ADMIN yang valid memiliki akses global.
            if ($accessibleUnitIds === null) {
                return true;
            }

            if ($accessibleUnitIds === []) {
                return false;
            }

            // Periksa penempatan pegawai yang masih berlaku.
            return $pegawai->penempatan()
                ->whereDate('mulai', '<=', Carbon::today())
                ->where(function ($query) {
                    $query->whereNull('selesai')
                        ->orWhereDate('selesai', '>=', Carbon::today());
                })
                ->whereIn('unit_id', $accessibleUnitIds)
                ->whereHas('unit', function ($query) {
                    $query->where('aktif', true);
                })
                ->exists();
        } catch (LogicException) {
            return false;
        }
    }
}
