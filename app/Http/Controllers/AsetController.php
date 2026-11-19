<?php

namespace App\Http\Controllers;

use App\Http\Resources\AsetResource;
use App\Models\Aset;
use App\Services\AsetAccessService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AsetController extends Controller
{
    public function __construct(
        private readonly AsetAccessService $asetAccessService,
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Aset::class);

        $query = $this->asetAccessService
            ->scopeVisibleAssets(Aset::query(), $request->user());

        if ($request->filled('q')) {
            $keyword = trim($request->string('q')->toString());

            $query->where(function ($builder) use ($keyword) {
                $builder->where('kode_aset', 'like', "%{$keyword}%")
                    ->orWhere('nama', 'like', "%{$keyword}%")
                    ->orWhere('merk', 'like', "%{$keyword}%")
                    ->orWhere('nomor_seri', 'like', "%{$keyword}%");
            });
        }

        foreach (['kategori_id', 'kondisi', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        $assets = $query
            ->with([
                'sumberAset',
                'unitPemilik',
                'kategori',
                'klasifikasiInformasi',
            ])
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return AsetResource::collection($assets);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Aset::class);

        $validated = $request->validate([
            'sumber_id' => ['required', 'integer', 'exists:sumber_aset,id'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'unit_pemilik_id' => ['required', 'integer', 'exists:unit_organisasi,id'],
            'kategori_id' => ['required', 'integer', 'exists:kategori_aset,id'],
            'klasifikasi_informasi_id' => ['nullable', 'integer', 'exists:klasifikasi_informasi,id'],
            'kode_aset' => ['required', 'string', 'max:255', 'unique:aset,kode_aset'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
            'merk' => ['required', 'string', 'max:150'],
            'nomor_seri' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'kondisi' => ['required', 'string', 'max:50'],
            'lokasi' => ['required', 'string', 'max:255'],
            'tanggal_pengadaan' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        if (! $this->asetAccessService->canManageUnit(
            $request->user(),
            (int) $validated['unit_pemilik_id']
        )) {
            return response()->json([
                'message' => 'Unit pemilik aset bukan SUBBIDANG dalam scope aktif Anda.',
            ], 403);
        }

        $aset = DB::transaction(function () use ($validated) {
            $aset = Aset::query()->create($validated);

            $this->auditService->record(
                'CREATE_ASET',
                'aset',
                (int) $aset->idx,
                null,
                $this->assetSnapshot($aset),
                'Inventaris aset dibuat.'
            );

            return $aset;
        });

        $aset->load([
            'sumberAset',
            'unitPemilik',
            'kategori',
            'klasifikasiInformasi',
        ]);

        return (new AsetResource($aset))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Aset $aset)
    {
        Gate::authorize('view', $aset);

        $aset->load([
            'sumberAset',
            'unitPemilik',
            'kategori',
            'klasifikasiInformasi',
            'penugasan.pegawai',
        ]);

        return new AsetResource($aset);
    }

    public function update(Request $request, Aset $aset)
    {
        Gate::authorize('update', $aset);

        $validated = $request->validate([
            'sumber_id' => ['sometimes', 'required', 'integer', 'exists:sumber_aset,id'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_pemilik_id' => ['prohibited'],
            'kategori_id' => ['sometimes', 'required', 'integer', 'exists:kategori_aset,id'],
            'klasifikasi_informasi_id' => ['sometimes', 'nullable', 'integer', 'exists:klasifikasi_informasi,id'],
            'kode_aset' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('aset', 'kode_aset')->ignore($aset->idx, 'idx'),
            ],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['sometimes', 'required', 'string'],
            'merk' => ['sometimes', 'required', 'string', 'max:150'],
            'nomor_seri' => ['sometimes', 'nullable', 'string', 'max:255'],
            'jumlah' => ['sometimes', 'required', 'integer', 'min:1'],
            'kondisi' => ['sometimes', 'required', 'string', 'max:50'],
            'lokasi' => ['sometimes', 'required', 'string', 'max:255'],
            'tanggal_pengadaan' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
        ]);

        $updatedAset = DB::transaction(function () use ($aset, $validated) {
            $lockedAset = Aset::query()
                ->whereKey($aset->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $dataLama = $this->assetSnapshot($lockedAset);

            $lockedAset->update($validated);
            $dataBaru = $this->assetSnapshot($lockedAset->fresh());

            $this->auditService->record(
                'UPDATE_ASET',
                'aset',
                (int) $lockedAset->idx,
                $dataLama,
                $dataBaru,
                'Data inventaris aset diperbarui.'
            );

            return $lockedAset->fresh();
        });

        $updatedAset->load([
            'sumberAset',
            'unitPemilik',
            'kategori',
            'klasifikasiInformasi',
        ]);

        return new AsetResource($updatedAset);
    }

    public function destroy(Aset $aset)
    {
        Gate::authorize('delete', $aset);

        DB::transaction(function () use ($aset) {
            $lockedAset = Aset::query()
                ->whereKey($aset->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAset->penugasan()->exists()) {
                abort(
                    409,
                    'Aset tidak dapat dihapus karena memiliki riwayat penugasan.'
                );
            }

            $snapshot = $this->assetSnapshot($lockedAset);

            $this->auditService->record(
                'DELETE_ASET',
                'aset',
                (int) $lockedAset->idx,
                $snapshot,
                null,
                'Inventaris aset dihapus.'
            );

            $lockedAset->delete();
        });

        return response()->json([
            'message' => 'Aset berhasil dihapus.',
        ]);
    }

    private function assetSnapshot(Aset $aset): array
    {
        $fields = [
            'idx',
            'sumber_id',
            'external_id',
            'unit_pemilik_id',
            'kategori_id',
            'klasifikasi_informasi_id',
            'kode_aset',
            'nama',
            'deskripsi',
            'merk',
            'nomor_seri',
            'jumlah',
            'kondisi',
            'lokasi',
            'tanggal_pengadaan',
            'status',
            'last_synced_at',
            'dibuat_pada',
            'diperbarui_pada',
        ];

        return array_intersect_key(
            $aset->getAttributes(),
            array_flip($fields)
        );
    }
}
