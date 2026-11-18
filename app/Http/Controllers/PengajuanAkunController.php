<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApprovePengajuanAkunRequest;
use App\Http\Requests\RejectPengajuanAkunRequest;
use App\Http\Resources\PengajuanAkunResource;
use App\Models\PengajuanAkun;
use App\Services\PengajuanAkunService;
use App\Services\RoleScopeService;
use App\Services\UnitAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use LogicException;

class PengajuanAkunController extends Controller
{
    public function __construct(
        private readonly PengajuanAkunService $service,
        private readonly RoleScopeService $roleScopeService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', PengajuanAkun::class);

        $validated = $request->validate([
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    PengajuanAkun::STATUS_MENUNGGU,
                    PengajuanAkun::STATUS_DISETUJUI,
                    PengajuanAkun::STATUS_DITOLAK,
                ]),
            ],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = PengajuanAkun::query()
            ->with([
                'user.pegawai.penempatan.unit',
                'peninjau.pegawai',
            ])
            ->orderByDesc('diajukan_pada');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $actor = $request->user();

        if (! $this->roleScopeService->hasGlobalAccess($actor)) {
            $unitIds = app(UnitAccessService::class)
                ->getAccessibleUnitIds($actor);

            $query->whereHas(
                'user.pegawai.penempatan',
                function ($placementQuery) use ($unitIds) {
                    $placementQuery
                        ->whereIn('unit_id', $unitIds)
                        ->whereDate('mulai', '<=', today())
                        ->where(function ($dateQuery) {
                            $dateQuery
                                ->whereNull('selesai')
                                ->orWhereDate('selesai', '>=', today());
                        })
                        ->whereHas('unit', fn ($unitQuery) => (
                            $unitQuery->where('aktif', true)
                        ));
                }
            );
        }

        return PengajuanAkunResource::collection(
            $query->paginate($validated['per_page'] ?? 15)
        );
    }

    public function show(PengajuanAkun $pengajuan)
    {
        Gate::authorize('view', $pengajuan);

        $pengajuan->load([
            'user.pegawai.penempatan.unit',
            'peninjau.pegawai',
        ]);

        return new PengajuanAkunResource($pengajuan);
    }

    public function approve(
        ApprovePengajuanAkunRequest $request,
        PengajuanAkun $pengajuan
    ): JsonResponse {
        $validated = $request->validated();

        Gate::authorize('approve', [
            $pengajuan,
            $validated['role_code'],
            $validated['unit_id'] ?? null,
        ]);

        try {
            $result = $this->service->approve(
                $pengajuan,
                $validated['role_code'],
                $validated['unit_id'] ?? null,
                $validated['catatan'] ?? null
            );

            return response()->json([
                'message' => 'Pengajuan akun berhasil disetujui.',
                'data' => new PengajuanAkunResource($result),
            ]);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function reject(
        RejectPengajuanAkunRequest $request,
        PengajuanAkun $pengajuan
    ): JsonResponse {
        Gate::authorize('reject', $pengajuan);

        try {
            $result = $this->service->reject(
                $pengajuan,
                $request->validated('catatan')
            );

            return response()->json([
                'message' => 'Pengajuan akun berhasil ditolak.',
                'data' => new PengajuanAkunResource($result),
            ]);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
