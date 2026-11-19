<?php

namespace App\Http\Controllers;

use App\Http\Resources\KategoriAsetResource;
use App\Models\KategoriAset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KategoriAsetController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', KategoriAset::class);

        $kategori = KategoriAset::query()
            ->with(['parentKategori', 'children'])
            ->orderBy('kode')
            ->get();

        return KategoriAsetResource::collection($kategori);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', KategoriAset::class);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:kategori_aset,id',
            ],
            'kode' => [
                'required',
                'string',
                'max:100',
                'unique:kategori_aset,kode',
            ],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $kategori = KategoriAset::create($validated);

        $kategori->load(['parentKategori', 'children']);

        return (new KategoriAsetResource($kategori))
            ->response()
            ->setStatusCode(201);
    }

    public function show(KategoriAset $kategoriAset)
    {
        Gate::authorize('view', $kategoriAset);

        $kategoriAset->load(['parentKategori', 'children']);

        return new KategoriAsetResource($kategoriAset);
    }

    public function update(
        Request $request,
        KategoriAset $kategoriAset
    ) {
        Gate::authorize('update', $kategoriAset);

        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                'exists:kategori_aset,id',
            ],
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kategori_aset', 'kode')
                    ->ignore($kategoriAset->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        if (
            isset($validated['parent_id'])
            && $this->wouldCreateCycle(
                $kategoriAset,
                (int) $validated['parent_id']
            )
        ) {
            throw ValidationException::withMessages([
                'parent_id' => [
                    'Kategori tidak boleh menjadi parent dari dirinya sendiri atau turunannya.',
                ],
            ]);
        }

        $kategoriAset->update($validated);

        $kategoriAset->load(['parentKategori', 'children']);

        return new KategoriAsetResource($kategoriAset);
    }

    public function destroy(KategoriAset $kategoriAset)
    {
        Gate::authorize('delete', $kategoriAset);

        $hasChildren = KategoriAset::query()
            ->where('parent_id', $kategoriAset->id)
            ->exists();

        $usedByAsset = DB::table('aset')
            ->where('kategori_id', $kategoriAset->id)
            ->exists();

        if ($hasChildren || $usedByAsset) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki subkategori atau digunakan oleh data aset.',
            ], 409);
        }

        $kategoriAset->delete();

        return response()->noContent();
    }

    private function wouldCreateCycle(
        KategoriAset $kategori,
        int $newParentId
    ): bool {
        $visited = [];
        $currentId = $newParentId;

        while ($currentId !== null) {
            if ($currentId === (int) $kategori->id) {
                return true;
            }

            if (isset($visited[$currentId])) {
                return true;
            }

            $visited[$currentId] = true;

            $currentId = KategoriAset::query()
                ->whereKey($currentId)
                ->value('parent_id');

            $currentId = $currentId === null
                ? null
                : (int) $currentId;
        }

        return false;
    }
}
