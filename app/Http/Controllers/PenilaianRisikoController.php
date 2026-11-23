<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenilaianRisikoRequest;
use App\Http\Resources\PenilaianRisikoResource;
use App\Models\Risiko;
use App\Services\PenilaianRisikoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use LogicException;

class PenilaianRisikoController extends Controller
{
    public function __construct(
        private readonly PenilaianRisikoService $service,
    ) {}

    public function index(Risiko $risiko)
    {
        Gate::authorize('viewPenilaian', $risiko);

        $penilaian = $risiko->penilaianRisiko()
            ->with([
                'matriks.kemungkinan',
                'matriks.dampak',
                'matriks.tingkatRisiko',
                'penilai.pegawai',
            ])
            ->orderBy('dinilai_pada')
            ->orderBy('id')
            ->get();

        return PenilaianRisikoResource::collection($penilaian);
    }

    public function store(
        StorePenilaianRisikoRequest $request,
        Risiko $risiko,
    ): JsonResponse|PenilaianRisikoResource {
        Gate::authorize('createPenilaian', $risiko);

        try {
            $penilaian = $this->service->create(
                $risiko,
                $request->user(),
                $request->validated(),
            );

            return (new PenilaianRisikoResource($penilaian))
                ->response()
                ->setStatusCode(201);
        } catch (LogicException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 409);
        }
    }
}
