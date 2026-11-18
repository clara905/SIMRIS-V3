<?php

namespace App\Http\Controllers;

use App\Http\Resources\UnitOrganisasiResource;
use App\Models\UnitOrganisasi;
use App\Services\UnitAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnitOrganisasiController extends Controller
{
    public function __construct(
        private readonly UnitAccessService $unitAccessService
    ) {}

    /**
     * Menampilkan daftar unit sesuai cakupan akses pengguna.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', UnitOrganisasi::class);

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($request->user());

        $query = UnitOrganisasi::query()
            ->where('aktif', true);

        // NULL berarti akses global SUPER_ADMIN.
        if ($accessibleUnitIds !== null) {
            $query->whereIn('id', $accessibleUnitIds);
        }

        $units = $query
            ->with($this->visibleHierarchyRelations($accessibleUnitIds))
            ->orderBy('kode')
            ->get();

        return UnitOrganisasiResource::collection($units);
    }

    /**
     * Menampilkan detail unit sesuai cakupan akses pengguna.
     */
    public function show(UnitOrganisasi $unit)
    {
        Gate::authorize('view', $unit);

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds(request()->user());

        $unit->load(
            $this->visibleHierarchyRelations($accessibleUnitIds)
        );

        return new UnitOrganisasiResource($unit);
    }

    /**
     * Membatasi parent dan children berdasarkan unit yang boleh diakses.
     */
    private function visibleHierarchyRelations(?array $accessibleUnitIds): array
    {
        $constraint = function ($query) use ($accessibleUnitIds) {
            $query->where('aktif', true);

            if ($accessibleUnitIds !== null) {
                $query->whereIn('id', $accessibleUnitIds);
            }
        };

        return [
            'parentUnit' => $constraint,
            'children' => $constraint,
        ];
    }
}
