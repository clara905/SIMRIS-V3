<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleAssignmentRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserRoleScopeResource;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Services\RoleScopeAssignmentService;
use Illuminate\Support\Facades\Gate;
use LogicException;

class RoleAssignmentController extends Controller
{
    public function __construct(
        private readonly RoleScopeAssignmentService $assignmentService
    ) {}

    /**
     * Daftar role yang dapat dipilih aktor.
     */
    public function roles()
    {
        Gate::authorize('viewAny', Role::class);

        $actorRole = request()->user()
            ->roleScopes()
            ->where('mulai', '<=', now())
            ->whereNull('selesai')
            ->with('role')
            ->first()
            ?->role
            ?->kode;

        $query = Role::query();

        if ($actorRole === 'ADMIN_SATKER') {
            $query->whereNotIn('kode', [
                'SUPER_ADMIN',
                'ADMIN_SATKER',
            ]);
        }

        return RoleResource::collection(
            $query->orderBy('kode')->get()
        );
    }

    /**
     * Riwayat role pengguna.
     */
    public function history(User $user)
    {
        Gate::authorize('viewHistory', [
            UserRoleScope::class,
            $user,
        ]);

        $scopes = UserRoleScope::query()
            ->where('user_id', $user->id)
            ->with(['role', 'unit'])
            ->orderByDesc('mulai')
            ->get();

        return UserRoleScopeResource::collection($scopes);
    }

    /**
     * Memberikan role baru atau mengganti role sebelumnya.
     */
    public function assign(
        RoleAssignmentRequest $request,
        User $user
    ) {
        $validated = $request->validated();

        Gate::authorize('assignRole', [
            UserRoleScope::class,
            $user,
            $validated['role_code'],
            $validated['unit_id'] ?? null,
        ]);

        try {
            $scope = $this->assignmentService->assignRole(
                $user,
                $validated['role_code'],
                $validated['unit_id'] ?? null
            );

            $scope->load(['role', 'unit']);

            return (new UserRoleScopeResource($scope))
                ->additional([
                    'message' => 'Penugasan role berhasil disimpan.',
                ])
                ->response()
                ->setStatusCode(200);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
