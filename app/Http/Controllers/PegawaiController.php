<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeactivatePegawaiRequest;
use App\Http\Requests\StorePegawaiRequest;
use App\Http\Requests\UpdatePegawaiRequest;
use App\Http\Resources\PegawaiResource;
use App\Models\Pegawai;
use App\Models\UnitOrganisasi;
use App\Services\AuditService;
use App\Services\PenempatanPegawaiService;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

class PegawaiController extends Controller
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService,
        private readonly PenempatanPegawaiService $penempatanService,
        private readonly AuditService $auditService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Pegawai::class);

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($request->user());

        $query = Pegawai::query()
            ->where('aktif', true);

        if ($accessibleUnitIds !== null) {
            $query->whereHas('penempatan', function ($placement) use (
                $accessibleUnitIds
            ) {
                $placement
                    ->whereDate('mulai', '<=', today())
                    ->where(function ($date) {
                        $date->whereNull('selesai')
                            ->orWhereDate('selesai', '>=', today());
                    })
                    ->whereIn('unit_id', $accessibleUnitIds)
                    ->whereHas('unit', function ($unit) {
                        $unit->where('aktif', true);
                    });
            });
        }

        if ($request->filled('q')) {
            $search = trim($request->string('q')->toString());

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('nip', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $pegawai = $query
            ->with($this->activePlacementRelations($accessibleUnitIds))
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        return PegawaiResource::collection($pegawai);
    }

    public function show(Pegawai $pegawai)
    {
        Gate::authorize('view', $pegawai);

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds(request()->user());

        $pegawai->load(
            $this->activePlacementRelations($accessibleUnitIds)
        );

        return new PegawaiResource($pegawai);
    }

    public function store(StorePegawaiRequest $request)
    {
        Gate::authorize('create', Pegawai::class);

        $validated = $request->validated();

        $this->authorizeUnit($request, (int) $validated['unit_id']);

        try {
            $pegawai = DB::transaction(function () use (
                $validated
            ) {
                $pegawai = Pegawai::create([
                    'nip' => $validated['nip'],
                    'nama' => $validated['nama'],
                    'email_kontak' => $validated['email_kontak'] ?? null,
                    'jenis_kepegawaian' => $validated['jenis_kepegawaian'],
                    'aktif' => true,
                ]);

                $this->penempatanService->assignInitial(
                    $pegawai,
                    (int) $validated['unit_id'],
                    $validated['tanggal_mulai']
                );

                $this->auditService->record(
                    aksi: 'CREATE_PEGAWAI',
                    entitas: 'pegawai',
                    entitasId: $pegawai->id,
                    dataLama: null,
                    dataBaru: $this->snapshot($pegawai),
                );

                return $pegawai;
            });
        } catch (LogicException $exception) {
            throw ValidationException::withMessages([
                'pegawai' => $exception->getMessage(),
            ]);
        }

        $pegawai->load(
            $this->activePlacementRelations(
                $this->unitAccessService->getAccessibleUnitIds(
                    $request->user()
                )
            )
        );

        return (new PegawaiResource($pegawai))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdatePegawaiRequest $request,
        Pegawai $pegawai
    ) {
        Gate::authorize('update', $pegawai);

        $validated = $request->validated();

        return DB::transaction(function () use (
            $validated,
            $pegawai
        ) {
            $lockedPegawai = Pegawai::query()
                ->lockForUpdate()
                ->findOrFail($pegawai->id);

            if (! $lockedPegawai->aktif) {
                throw ValidationException::withMessages([
                    'pegawai' => 'Pegawai tidak aktif.',
                ]);
            }

            $oldSnapshot = $this->snapshot($lockedPegawai);

            $lockedPegawai->update([
                'nip' => $validated['nip'],
                'nama' => $validated['nama'],
                'email_kontak' => $validated['email_kontak'] ?? null,
                'jenis_kepegawaian' => $validated['jenis_kepegawaian'],
            ]);

            $this->auditService->record(
                aksi: 'UPDATE_PEGAWAI',
                entitas: 'pegawai',
                entitasId: $lockedPegawai->id,
                dataLama: $oldSnapshot,
                dataBaru: $this->snapshot($lockedPegawai),
            );

            return new PegawaiResource(
                $lockedPegawai->load(
                    $this->activePlacementRelations(
                        $this->unitAccessService->getAccessibleUnitIds(
                            request()->user()
                        )
                    )
                )
            );
        });
    }

    public function deactivate(
        DeactivatePegawaiRequest $request,
        Pegawai $pegawai
    ) {
        Gate::authorize('deactivate', $pegawai);

        if (! $pegawai->aktif) {
            throw ValidationException::withMessages([
                'pegawai' => 'Pegawai sudah tidak aktif.',
            ]);
        }

        $validated = $request->validated();

        try {
            $result = DB::transaction(function () use (
                $pegawai,
                $validated
            ) {
                $lockedPegawai = Pegawai::query()
                    ->lockForUpdate()
                    ->findOrFail($pegawai->id);

                if (! $lockedPegawai->aktif) {
                    throw new LogicException(
                        'Pegawai sudah tidak aktif.'
                    );
                }

                $oldSnapshot = $this->snapshot($lockedPegawai);

                $this->penempatanService->close(
                    $lockedPegawai,
                    $validated['tanggal_selesai']
                );

                $lockedPegawai->update([
                    'aktif' => false,
                ]);

                $this->auditService->record(
                    aksi: 'DEACTIVATE_PEGAWAI',
                    entitas: 'pegawai',
                    entitasId: $lockedPegawai->id,
                    dataLama: $oldSnapshot,
                    dataBaru: $this->snapshot($lockedPegawai),
                    catatan: $validated['catatan'] ?? null,
                );

                return $lockedPegawai;
            });
        } catch (LogicException $exception) {
            throw ValidationException::withMessages([
                'pegawai' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Pegawai berhasil dinonaktifkan.',
            'data' => new PegawaiResource($result),
        ]);
    }

    private function authorizeUnit(
        Request $request,
        int $unitId
    ): void {
        $unit = UnitOrganisasi::query()
            ->whereKey($unitId)
            ->where('aktif', true)
            ->first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'Unit tidak ditemukan atau tidak aktif.',
            ]);
        }

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($request->user());

        if (
            $accessibleUnitIds !== null
            && ! in_array($unitId, $accessibleUnitIds, true)
        ) {
            throw ValidationException::withMessages([
                'unit_id' => 'Unit berada di luar cakupan kewenangan.',
            ]);
        }
    }

    private function activePlacementRelations(
        ?array $accessibleUnitIds
    ): array {
        return [
            'penempatan' => function ($query) use (
                $accessibleUnitIds
            ) {
                $query
                    ->whereDate('mulai', '<=', today())
                    ->where(function ($date) {
                        $date->whereNull('selesai')
                            ->orWhereDate('selesai', '>=', today());
                    })
                    ->whereHas('unit', function ($unit) use (
                        $accessibleUnitIds
                    ) {
                        $unit->where('aktif', true);

                        if ($accessibleUnitIds !== null) {
                            $unit->whereIn('id', $accessibleUnitIds);
                        }
                    })
                    ->with('unit')
                    ->orderByDesc('mulai');
            },
        ];
    }

    private function snapshot(Pegawai $pegawai): array
    {
        return [
            'pegawai_id' => $pegawai->id,
            'nip' => $pegawai->nip,
            'nama' => $pegawai->nama,
            'email_kontak' => $pegawai->email_kontak,
            'jenis_kepegawaian' => $pegawai->jenis_kepegawaian,
            'aktif' => $pegawai->aktif,
        ];
    }
}
