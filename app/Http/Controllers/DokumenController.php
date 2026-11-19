<?php

namespace App\Http\Controllers;

use App\Http\Resources\DokumenResource;
use App\Models\Dokumen;
use App\Models\UnitOrganisasi;
use App\Services\AsetAccessService;
use App\Services\DokumenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DokumenController extends Controller
{
    public function __construct(
        private readonly AsetAccessService $asetAccessService,
        private readonly DokumenService $dokumenService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Dokumen::class);

        $validated = $request->validate([
            'unit_id' => ['sometimes', 'integer'],
            'jenis' => ['sometimes', 'string', 'max:100'],
        ]);

        $visibleIds = $this->asetAccessService
            ->getVisibleSubbidangIds($request->user());

        $query = Dokumen::query()
            ->with(['unitPemilik', 'pembuat'])
            ->whereHas('unitPemilik', function ($unitQuery) {
                $unitQuery
                    ->where('aktif', true)
                    ->where('jenis', 'SUBBIDANG');
            });

        if ($visibleIds !== null) {
            $query->whereIn('unit_pemilik_id', $visibleIds);
        }

        if (isset($validated['unit_id'])) {
            $query->where(
                'unit_pemilik_id',
                $validated['unit_id']
            );
        }

        if (isset($validated['jenis'])) {
            $query->where('jenis', $validated['jenis']);
        }

        return DokumenResource::collection(
            $query->orderByDesc('dibuat_pada')
                ->paginate(15)
        );
    }

    public function show(Dokumen $dokumen)
    {
        Gate::authorize('view', $dokumen);

        return new DokumenResource(
            $dokumen->load(['unitPemilik', 'pembuat'])
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Dokumen::class);

        $validated = $request->validate([
            'unit_pemilik_id' => [
                'required',
                'integer',
                'exists:unit_organisasi,id',
            ],
            'judul' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:100'],
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ]);

        $unit = UnitOrganisasi::query()
            ->whereKey($validated['unit_pemilik_id'])
            ->where('aktif', true)
            ->where('jenis', 'SUBBIDANG')
            ->first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_pemilik_id' => 'Unit pemilik harus merupakan SUBBIDANG aktif.',
            ]);
        }

        if (! $this->asetAccessService->canManageUnit(
            $request->user(),
            (int) $unit->id
        )) {
            abort(403, 'Anda tidak berwenang mengunggah dokumen untuk unit ini.');
        }

        $dokumen = $this->dokumenService->upload(
            $request->user(),
            $unit,
            $validated['file'],
            $validated['judul'],
            $validated['jenis']
        );

        return (new DokumenResource($dokumen))
            ->response()
            ->setStatusCode(201);
    }

    public function download(Dokumen $dokumen)
    {
        Gate::authorize('view', $dokumen);

        return $this->dokumenService->download($dokumen);
    }
}
