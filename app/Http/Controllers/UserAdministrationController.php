<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStatusRequest;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use LogicException;

class UserAdministrationController extends Controller
{
    public function __construct(
        private readonly UserAdministrationService $service
    ) {}

    public function deactivate(
        UserStatusRequest $request,
        User $user
    ): JsonResponse {
        Gate::authorize('deactivate', $user);

        try {
            $result = $this->service->deactivate(
                $user,
                $request->validated('catatan')
            );

            return response()->json([
                'message' => 'Akun berhasil dinonaktifkan.',
                'data' => [
                    'id' => $result->id,
                    'username' => $result->username,
                    'aktif' => $result->aktif,
                ],
            ]);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function reactivate(
        UserStatusRequest $request,
        User $user
    ): JsonResponse {
        Gate::authorize('reactivate', $user);

        try {
            $result = $this->service->reactivate(
                $user,
                $request->validated('catatan')
            );

            return response()->json([
                'message' => 'Akun berhasil diaktifkan kembali.',
                'data' => [
                    'id' => $result->id,
                    'username' => $result->username,
                    'aktif' => $result->aktif,
                ],
            ]);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
