<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferPegawaiRequest;
use App\Models\Pegawai;
use App\Models\PenempatanPegawai;
use App\Models\UnitOrganisasi;
use App\Services\PenempatanPegawaiService;
use App\Services\UnitAccessService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

class PenempatanPegawaiController extends Controller
{
    public function __construct(
        private readonly PenempatanPegawaiService $penempatanService,
        private readonly UnitAccessService $unitAccessService,
    ) {}

    /**
     * Transfer pegawai ke unit organisasi baru.
     */
    public function transfer(
        TransferPegawaiRequest $request,
        Pegawai $pegawai
    ) {
        Gate::authorize('update', $pegawai);

        $validated = $request->validated();

        $unitTujuan = UnitOrganisasi::query()
            ->whereKey($validated['unit_tujuan_id'])
            ->where('aktif', true)
            ->first();

        if (! $unitTujuan) {
            throw ValidationException::withMessages([
                'unit_tujuan_id' => 'Unit tujuan tidak ditemukan atau tidak aktif.',
            ]);
        }

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds($request->user());

        if (
            $accessibleUnitIds !== null
            && ! in_array(
                (int) $unitTujuan->id,
                $accessibleUnitIds,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'unit_tujuan_id' => 'Unit tujuan berada di luar cakupan kewenangan.',
            ]);
        }

        try {
            $placement = $this->penempatanService->transfer(
                $pegawai,
                (int) $unitTujuan->id,
                $validated['tanggal_efektif'],
            );
        } catch (LogicException $exception) {
            throw ValidationException::withMessages([
                'penempatan' => $exception->getMessage(),
            ]);
        }

        $placement->load(['pegawai', 'unit']);

        return response()->json([
            'message' => 'Penempatan pegawai berhasil dipindahkan.',
            'data' => [
                'pegawai_id' => $placement->pegawai_id,
                'nip' => $placement->pegawai?->nip,
                'nama' => $placement->pegawai?->nama,
                'penempatan_baru' => [
                    'id' => $placement->id,
                    'unit_id' => $placement->unit_id,
                    'unit_kode' => $placement->unit?->kode,
                    'unit_nama' => $placement->unit?->nama,
                    'mulai' => $placement->mulai?->toDateString(),
                    'selesai' => $placement->selesai?->toDateString(),
                ],
            ],
        ]);
    }

    public function cancelTransfer(Pegawai $pegawai)
    {
        Gate::authorize('update', $pegawai);

        try {
            $this->penempatanService->cancelScheduledTransfer($pegawai);
        } catch (LogicException $exception) {
            throw ValidationException::withMessages([
                'penempatan' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Transfer pegawai terjadwal berhasil dibatalkan.',
        ]);
    }

    /**
     * Menampilkan riwayat penempatan pegawai.
     */
    public function history(Pegawai $pegawai)
    {
        Gate::authorize('view', $pegawai);

        $accessibleUnitIds = $this->unitAccessService
            ->getAccessibleUnitIds(request()->user());

        $query = PenempatanPegawai::query()
            ->with('unit')
            ->where('pegawai_id', $pegawai->id);

        if ($accessibleUnitIds !== null) {
            $query->whereIn('unit_id', $accessibleUnitIds);
        }

        $history = $query
            ->orderByDesc('mulai')
            ->orderByDesc('id')
            ->get()
            ->map(function (PenempatanPegawai $placement) {
                return [
                    'id' => $placement->id,
                    'unit_id' => $placement->unit_id,
                    'unit' => $placement->unit ? [
                        'kode' => $placement->unit->kode,
                        'nama' => $placement->unit->nama,
                        'jenis' => $placement->unit->jenis,
                    ] : null,
                    'mulai' => $placement->mulai?->toDateString(),
                    'selesai' => $placement->selesai?->toDateString(),
                ];
            });

        return response()->json([
            'data' => [
                'pegawai' => [
                    'id' => $pegawai->id,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama,
                ],
                'riwayat_penempatan' => $history,
            ],
        ]);
    }
}
