<?php

namespace App\Http\Controllers;

use App\Http\Resources\PenugasanAsetResource;
use App\Models\Aset;
use App\Models\PenugasanAset;
use App\Services\PenugasanAsetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PenugasanAsetController extends Controller
{
    public function __construct(
        private readonly PenugasanAsetService $penugasanAsetService
    ) {}

    public function index(Aset $aset)
    {
        Gate::authorize('viewAny', [PenugasanAset::class, $aset]);

        $assignments = $this->penugasanAsetService
            ->visibleAssignments($aset, request()->user());

        return PenugasanAsetResource::collection(
            $assignments->paginate(20)
        );
    }

    public function store(Request $request, Aset $aset)
    {
        Gate::authorize('create', [PenugasanAset::class, $aset]);

        $validated = $request->validate([
            'pegawai_id' => [
                'required',
                'integer',
                'exists:pegawai,id',
            ],
            'jenis_tanggung_jawab' => [
                'required',
                'string',
                'max:100',
            ],
            'mulai' => [
                'required',
                'date',
            ],
            'selesai' => [
                'nullable',
                'date',
                'after_or_equal:mulai',
            ],
        ]);

        $assignment = $this->penugasanAsetService->create(
            $aset,
            $request->user(),
            $validated
        );

        return (new PenugasanAsetResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    public function end(Request $request, PenugasanAset $penugasan)
    {
        Gate::authorize('end', $penugasan);

        $validated = $request->validate([
            'tanggal_selesai' => [
                'required',
                'date',
            ],
        ]);

        $assignment = $this->penugasanAsetService->end(
            $penugasan,
            $request->user(),
            $validated['tanggal_selesai']
        );

        return new PenugasanAsetResource($assignment);
    }
}
