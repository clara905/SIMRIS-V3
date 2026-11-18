<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\RoleScopeService;
use Closure;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Autentikasi diperlukan.');
        }

        try {
            $scope = $this->roleScopeService->getActiveScope($user);

            if (! $scope) {
                abort(403, 'Akun tidak memiliki role aktif.');
            }

            $roleCode = $scope->role?->kode;

            if (! in_array($roleCode, $roles, true)) {
                abort(403, 'Role tidak memiliki izin mengakses fitur ini.');
            }

            // SUPER_ADMIN tidak membutuhkan unit spesifik.
            if ($roleCode === 'SUPER_ADMIN') {
                if (! $this->roleScopeService->hasGlobalAccess($user)) {
                    abort(403, 'Akses global tidak valid.');
                }
            } else {
                $unitId = $this->roleScopeService
                    ->getEffectiveUnitId($user);

                if ($unitId === null) {
                    abort(403, 'Unit akses pengguna tidak valid.');
                }
            }
        } catch (LogicException) {
            abort(403, 'Data role atau cakupan akses tidak valid.');
        }

        return $next($request);
    }
}
