<?php

namespace App\Http\Middleware;

use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Services\UnitAccessService;
use Closure;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnitAccess
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $parameter = 'unit_id'
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Autentikasi diperlukan.');
        }

        try {
            $accessibleUnitIds = $this->unitAccessService
                ->getAccessibleUnitIds($user);

            // NULL hanya berarti akses global yang sudah divalidasi
            // oleh UnitAccessService untuk SUPER_ADMIN.
            $isGlobal = $accessibleUnitIds === null;

            // Simpan cakupan untuk digunakan controller/query berikutnya.
            $request->attributes->set(
                'simris_accessible_unit_ids',
                $accessibleUnitIds
            );

            $request->attributes->set(
                'simris_global_unit_access',
                $isGlobal
            );

            // Prioritaskan parameter route, kemudian query string.
            $targetUnit = $request->route($parameter)
                ?? $request->query($parameter);

            // Jika tidak ada unit spesifik, controller harus tetap
            // membatasi query menggunakan cakupan yang disediakan.
            if ($targetUnit === null) {
                return $next($request);
            }

            if ($targetUnit instanceof UnitOrganisasi) {
                $targetUnit = $targetUnit->id;
            }

            if (
                ! is_string($targetUnit)
                && ! is_int($targetUnit)
            ) {
                abort(403, 'Parameter unit tidak valid.');
            }

            $targetUnitId = filter_var(
                $targetUnit,
                FILTER_VALIDATE_INT
            );

            if ($targetUnitId === false || $targetUnitId < 1) {
                abort(403, 'Parameter unit tidak valid.');
            }

            if (
                ! $isGlobal
                && ! in_array(
                    $targetUnitId,
                    $accessibleUnitIds,
                    true
                )
            ) {
                abort(403, 'Anda tidak memiliki akses ke unit ini.');
            }
        } catch (LogicException) {
            abort(403, 'Cakupan akses unit tidak valid.');
        }

        return $next($request);
    }
}
