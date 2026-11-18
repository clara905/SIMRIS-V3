<?php

namespace App\Policies;

use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\UnitAccessService;
use LogicException;

class UnitOrganisasiPolicy
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Menentukan apakah user boleh melihat daftar unit.
     */
    public function viewAny(User $user): bool
    {
        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            // NULL hanya diberikan kepada SUPER_ADMIN yang valid.
            return $accessibleUnitIds === null
                || $accessibleUnitIds !== [];
        } catch (LogicException) {
            return false;
        }
    }

    /**
     * Menentukan apakah user boleh melihat unit tertentu.
     */
    public function view(
        User $user,
        UnitOrganisasi $unit
    ): bool {
        if (! $unit->aktif) {
            return false;
        }

        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            // SUPER_ADMIN memiliki akses global.
            if ($accessibleUnitIds === null) {
                return true;
            }

            return in_array(
                (int) $unit->id,
                $accessibleUnitIds,
                true
            );
        } catch (LogicException) {
            return false;
        }
    }
}
