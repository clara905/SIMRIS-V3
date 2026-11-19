<?php

namespace App\Http\Controllers;

use App\Http\Resources\KlasifikasiInformasiResource;
use App\Models\KlasifikasiInformasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class KlasifikasiInformasiController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', KlasifikasiInformasi::class);

        return KlasifikasiInformasiResource::collection(
            KlasifikasiInformasi::query()
                ->orderBy('kode')
                ->get()
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('create', KlasifikasiInformasi::class);

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:100',
                'unique:klasifikasi_informasi,kode',
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
        ]);

        $klasifikasi = KlasifikasiInformasi::create($validated);

        return (new KlasifikasiInformasiResource($klasifikasi))
            ->response()
            ->setStatusCode(201);
    }

    public function show(KlasifikasiInformasi $klasifikasiInformasi)
    {
        Gate::authorize('view', $klasifikasiInformasi);

        return new KlasifikasiInformasiResource($klasifikasiInformasi);
    }

    public function update(
        Request $request,
        KlasifikasiInformasi $klasifikasiInformasi
    ) {
        Gate::authorize('update', $klasifikasiInformasi);

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:100',
                Rule::unique('klasifikasi_informasi', 'kode')
                    ->ignore($klasifikasiInformasi->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['required', 'string'],
        ]);

        $klasifikasiInformasi->update($validated);

        return new KlasifikasiInformasiResource(
            $klasifikasiInformasi->refresh()
        );
    }

    public function destroy(
        KlasifikasiInformasi $klasifikasiInformasi
    ) {
        Gate::authorize('delete', $klasifikasiInformasi);

        $used = DB::table('aset')
            ->where(
                'klasifikasi_informasi_id',
                $klasifikasiInformasi->id
            )
            ->exists();

        if ($used) {
            return response()->json([
                'message' => 'Klasifikasi tidak dapat dihapus karena masih digunakan oleh data aset.',
            ], 409);
        }

        $klasifikasiInformasi->delete();

        return response()->noContent();
    }
}
