<?php

namespace App\Services;

use App\Models\UnitOrganisasi;
use App\Models\User;
use LogicException;

class UnitAccessService
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    /**
     * Mengambil seluruh ID unit yang dapat diakses user.
     *
     * NULL = akses global SUPER_ADMIN.
     * Array kosong = tidak memiliki akses unit.
     */
    public function getAccessibleUnitIds(User $user): ?array
    {
        if ($this->roleScopeService->hasGlobalAccess($user)) {
            return null;
        }

        $scope = $this->roleScopeService->getActiveScope($user);

        if (! $scope) {
            return [];
        }

        $roleCode = $scope->role?->kode;

        $rootUnitId = $this->roleScopeService
            ->getEffectiveUnitId($user);

        if ($rootUnitId === null) {
            return [];
        }

        // STAFF hanya dapat mengakses unit penempatannya.
        if ($roleCode === 'STAFF') {
            return [$rootUnitId];
        }

        $expectedTypes = [
            'ADMIN_SATKER' => 'SATKER',
            'KASATKER' => 'SATKER',
            'KABID' => 'BIDANG',
            'KASUB' => 'SUBBIDANG',
        ];

        if (! isset($expectedTypes[$roleCode])) {
            return [];
        }

        return $this->getActiveDescendantUnitIds(
            $rootUnitId,
            $expectedTypes[$roleCode]
        );
    }

    /**
     * Mengambil unit induk beserta seluruh turunannya yang aktif.
     */
    private function getActiveDescendantUnitIds(
        int $rootUnitId,
        string $expectedType
    ): array {
        $root = UnitOrganisasi::query()
            ->whereKey($rootUnitId)
            ->where('aktif', true)
            ->first();

        if (! $root) {
            return [];
        }

        if ($root->jenis !== $expectedType) {
            throw new LogicException(
                'Jenis unit tidak sesuai dengan role pengguna.'
            );
        }

        $result = [$root->id];
        $visited = [$root->id => true];
        $frontier = [$root->id];

        while ($frontier !== []) {
            $children = UnitOrganisasi::query()
                ->whereIn('parent_id', $frontier)
                ->where('aktif', true)
                ->pluck('id')
                ->all();

            $frontier = [];

            foreach ($children as $childId) {
                if (isset($visited[$childId])) {
                    continue;
                }

                $visited[$childId] = true;
                $result[] = (int) $childId;
                $frontier[] = (int) $childId;
            }
        }

        return $result;
    }
}
