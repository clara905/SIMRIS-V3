<?php

namespace App\Http\Controllers;

use App\Http\Resources\VersiMetodeRisikoResource;
use App\Models\VersiMetodeRisiko;
use App\Services\MetodeRisikoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use LogicException;

class MetodeRisikoController extends Controller
{
    public function __construct(
        private readonly MetodeRisikoService $metodeRisikoService
    ) {}

    /**
     * Daftar seluruh versi metode risiko.
     */
    public function index()
    {
        Gate::authorize('viewAny', VersiMetodeRisiko::class);

        $versi = VersiMetodeRisiko::query()
            ->orderByDesc('id')
            ->get();

        return VersiMetodeRisikoResource::collection($versi);
    }

    /**
     * Detail metode beserta seluruh konfigurasi.
     */
    public function show(VersiMetodeRisiko $versi)
    {
        Gate::authorize('view', $versi);

        $versi->load([
            'skalaKemungkinan',
            'skalaDampak',
            'tingkatRisiko',
            'matriksRisiko.kemungkinan',
            'matriksRisiko.dampak',
            'matriksRisiko.tingkatRisiko',
        ]);

        return new VersiMetodeRisikoResource($versi);
    }

    /**
     * Membuat draft metode risiko.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', VersiMetodeRisiko::class);

        $versi = $this->metodeRisikoService->createDraft(
            $request->only([
                'kode_versi',
                'nama',
                'deskripsi',
            ])
        );

        return (new VersiMetodeRisikoResource($versi))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mengganti konfigurasi metode yang masih draft.
     */
    public function configure(
        Request $request,
        VersiMetodeRisiko $versi
    ): JsonResponse|VersiMetodeRisikoResource {
        Gate::authorize('update', $versi);

        try {
            $configured = $this->metodeRisikoService->configureDraft(
                $versi,
                $request->only([
                    'kemungkinan',
                    'dampak',
                    'tingkat_risiko',
                    'matriks',
                ])
            );

            return new VersiMetodeRisikoResource($configured);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }

    /**
     * Membekukan metode risiko.
     */
    public function freeze(
        VersiMetodeRisiko $versi
    ): JsonResponse|VersiMetodeRisikoResource {
        Gate::authorize('freeze', $versi);

        try {
            $frozen = $this->metodeRisikoService->freeze($versi);

            return new VersiMetodeRisikoResource($frozen);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }
}
