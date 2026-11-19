<?php

namespace App\Http\Controllers;

use App\Http\Resources\SumberAsetResource;
use App\Models\SumberAset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SumberAsetController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', SumberAset::class);

        return SumberAsetResource::collection(
            SumberAset::query()->orderBy('kode')->get()
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', SumberAset::class);

        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:100', 'unique:sumber_aset,kode'],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $sumber = SumberAset::create($validated);

        return (new SumberAsetResource($sumber))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SumberAset $sumberAset)
    {
        Gate::authorize('view', $sumberAset);

        return new SumberAsetResource($sumberAset);
    }

    public function update(Request $request, SumberAset $sumberAset)
    {
        Gate::authorize('update', $sumberAset);

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sumber_aset', 'kode')
                    ->ignore($sumberAset->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $sumberAset->update($validated);

        return new SumberAsetResource($sumberAset->refresh());
    }

    public function destroy(SumberAset $sumberAset)
    {
        Gate::authorize('delete', $sumberAset);

        $used = DB::table('aset')
            ->where('sumber_id', $sumberAset->id)
            ->exists();

        if ($used) {
            return response()->json([
                'message' => 'Sumber aset tidak dapat dihapus karena masih digunakan oleh data aset.',
            ], 409);
        }

        $sumberAset->delete();

        return response()->noContent();
    }
}
