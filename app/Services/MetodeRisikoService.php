<?php

namespace App\Services;

use App\Models\VersiMetodeRisiko;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

class MetodeRisikoService
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function createDraft(array $data): VersiMetodeRisiko
    {
        $validated = Validator::make($data, [
            'kode_versi' => ['required', 'string', 'max:50', 'unique:versi_metode_risiko,kode_versi'],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ])->validate();

        return DB::transaction(function () use ($validated): VersiMetodeRisiko {
            $versi = VersiMetodeRisiko::query()->create([
                'kode_versi' => trim($validated['kode_versi']),
                'nama' => trim($validated['nama']),
                'deskripsi' => $validated['deskripsi'] ?? null,
                'dibekukan' => false,
                'dibekukan_pada' => null,
            ]);

            $this->auditService->record(
                aksi: 'CREATE_DRAFT_METODE_RISIKO',
                entitas: 'versi_metode_risiko',
                entitasId: $versi->id,
                dataBaru: [
                    'kode_versi' => $versi->kode_versi,
                    'nama' => $versi->nama,
                    'dibekukan' => false,
                ],
            );

            return $versi->refresh();
        });
    }

    /**
     * Mengganti seluruh konfigurasi versi draft dalam satu transaksi.
     *
     * Bentuk data:
     * kemungkinan: [{nilai, nama, deskripsi?}]
     * dampak: [{nilai, nama, deskripsi?}]
     * tingkat_risiko: [{kode, nama, urutan}]
     * matriks: [{
     *   kemungkinan_nilai, dampak_nilai,
     *   tingkat_risiko_kode, skor, rekomendasi_keputusan?
     * }]
     */
    public function configureDraft(
        VersiMetodeRisiko $versi,
        array $data,
    ): VersiMetodeRisiko {
        $validated = $this->validateConfiguration($data);

        return DB::transaction(function () use (
            $versi,
            $validated,
        ): VersiMetodeRisiko {
            $locked = VersiMetodeRisiko::query()
                ->lockForUpdate()
                ->findOrFail($versi->id);

            $this->assertDraft($locked);

            $oldSnapshot = [
                'kemungkinan' => $locked->skalaKemungkinan()->count(),
                'dampak' => $locked->skalaDampak()->count(),
                'tingkat_risiko' => $locked->tingkatRisiko()->count(),
                'matriks' => $locked->matriksRisiko()->count(),
            ];

            // Hapus matriks lebih dahulu karena mereferensikan ketiga master.
            $locked->matriksRisiko()->delete();
            $locked->skalaKemungkinan()->delete();
            $locked->skalaDampak()->delete();
            $locked->tingkatRisiko()->delete();

            $kemungkinanByValue = [];

            foreach ($validated['kemungkinan'] as $item) {
                $scale = $locked->skalaKemungkinan()->create($item);
                $kemungkinanByValue[$scale->nilai] = $scale;
            }

            $dampakByValue = [];

            foreach ($validated['dampak'] as $item) {
                $scale = $locked->skalaDampak()->create($item);
                $dampakByValue[$scale->nilai] = $scale;
            }

            $tingkatByCode = [];

            foreach ($validated['tingkat_risiko'] as $item) {
                $level = $locked->tingkatRisiko()->create($item);
                $tingkatByCode[$level->kode] = $level;
            }

            foreach ($validated['matriks'] as $item) {
                $kemungkinan = $kemungkinanByValue[$item['kemungkinan_nilai']];
                $dampak = $dampakByValue[$item['dampak_nilai']];
                $tingkat = $tingkatByCode[$item['tingkat_risiko_kode']];

                // Pemeriksaan eksplisit: semua referensi harus satu versi.
                if (
                    $kemungkinan->versi_metode_id !== $locked->id
                    || $dampak->versi_metode_id !== $locked->id
                    || $tingkat->versi_metode_id !== $locked->id
                ) {
                    throw new LogicException(
                        'Seluruh referensi matriks harus berasal dari versi metode yang sama.'
                    );
                }

                $locked->matriksRisiko()->create([
                    'kemungkinan_id' => $kemungkinan->id,
                    'dampak_id' => $dampak->id,
                    'tingkat_risiko_id' => $tingkat->id,
                    'skor' => $item['skor'],
                    'rekomendasi_keputusan' => $item['rekomendasi_keputusan'] ?? null,
                ]);
            }

            $newSnapshot = [
                'kemungkinan' => count($validated['kemungkinan']),
                'dampak' => count($validated['dampak']),
                'tingkat_risiko' => count($validated['tingkat_risiko']),
                'matriks' => count($validated['matriks']),
            ];

            $this->auditService->record(
                aksi: 'CONFIGURE_METODE_RISIKO',
                entitas: 'versi_metode_risiko',
                entitasId: $locked->id,
                dataLama: $oldSnapshot,
                dataBaru: $newSnapshot,
            );

            return $locked->refresh()->load([
                'skalaKemungkinan',
                'skalaDampak',
                'tingkatRisiko',
                'matriksRisiko',
            ]);
        });
    }

    public function freeze(VersiMetodeRisiko $versi): VersiMetodeRisiko
    {
        return DB::transaction(function () use ($versi): VersiMetodeRisiko {
            $locked = VersiMetodeRisiko::query()
                ->lockForUpdate()
                ->findOrFail($versi->id);

            $this->assertDraft($locked);
            $this->assertConfigurationComplete($locked);

            $oldData = [
                'dibekukan' => false,
                'dibekukan_pada' => null,
            ];

            $locked->forceFill([
                'dibekukan' => true,
                'dibekukan_pada' => now(),
            ])->save();

            $this->auditService->record(
                aksi: 'FREEZE_METODE_RISIKO',
                entitas: 'versi_metode_risiko',
                entitasId: $locked->id,
                dataLama: $oldData,
                dataBaru: [
                    'dibekukan' => true,
                    'dibekukan_pada' => $locked->dibekukan_pada?->toDateTimeString(),
                ],
            );

            return $locked->refresh();
        });
    }

    public function assertDraft(VersiMetodeRisiko $versi): void
    {
        if ($versi->dibekukan) {
            throw new LogicException(
                'Metode risiko sudah dibekukan dan tidak dapat diubah.'
            );
        }
    }

    private function validateConfiguration(array $data): array
    {
        $validated = Validator::make($data, [
            'kemungkinan' => ['required', 'array', 'size:5'],
            'kemungkinan.*.nilai' => ['required', 'integer', 'between:1,5', 'distinct'],
            'kemungkinan.*.nama' => ['required', 'string', 'max:100'],
            'kemungkinan.*.deskripsi' => ['nullable', 'string'],

            'dampak' => ['required', 'array', 'size:5'],
            'dampak.*.nilai' => ['required', 'integer', 'between:1,5', 'distinct'],
            'dampak.*.nama' => ['required', 'string', 'max:100'],
            'dampak.*.deskripsi' => ['nullable', 'string'],

            'tingkat_risiko' => ['required', 'array', 'min:1'],
            'tingkat_risiko.*.kode' => ['required', 'string', 'max:50', 'distinct'],
            'tingkat_risiko.*.nama' => ['required', 'string', 'max:100'],
            'tingkat_risiko.*.urutan' => ['required', 'integer', 'min:1', 'max:255', 'distinct'],

            'matriks' => ['required', 'array', 'size:25'],
            'matriks.*.kemungkinan_nilai' => ['required', 'integer', 'between:1,5'],
            'matriks.*.dampak_nilai' => ['required', 'integer', 'between:1,5'],
            'matriks.*.tingkat_risiko_kode' => ['required', 'string', 'max:50'],
            'matriks.*.skor' => ['required', 'integer', 'min:0'],
            'matriks.*.rekomendasi_keputusan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $likelihoodValues = collect($validated['kemungkinan'])
            ->pluck('nilai')
            ->sort()
            ->values()
            ->all();

        $impactValues = collect($validated['dampak'])
            ->pluck('nilai')
            ->sort()
            ->values()
            ->all();

        if ($likelihoodValues !== [1, 2, 3, 4, 5]) {
            throw ValidationException::withMessages([
                'kemungkinan' => 'Nilai skala kemungkinan harus lengkap dari 1 sampai 5.',
            ]);
        }

        if ($impactValues !== [1, 2, 3, 4, 5]) {
            throw ValidationException::withMessages([
                'dampak' => 'Nilai skala dampak harus lengkap dari 1 sampai 5.',
            ]);
        }

        $levelCodes = collect($validated['tingkat_risiko'])
            ->pluck('kode')
            ->all();

        $combinations = collect($validated['matriks'])->map(
            fn (array $item): string => $item['kemungkinan_nilai']
                .'-'.$item['dampak_nilai']
        );

        if ($combinations->unique()->count() !== 25) {
            throw ValidationException::withMessages([
                'matriks' => 'Matriks harus memiliki 25 kombinasi kemungkinan dan dampak yang unik.',
            ]);
        }

        foreach ($validated['matriks'] as $index => $item) {
            if (! in_array($item['tingkat_risiko_kode'], $levelCodes, true)) {
                throw ValidationException::withMessages([
                    "matriks.{$index}.tingkat_risiko_kode" => 'Kode tingkat risiko tidak ditemukan pada konfigurasi versi ini.',
                ]);
            }
        }

        return $validated;
    }

    private function assertConfigurationComplete(
        VersiMetodeRisiko $versi,
    ): void {
        $kemungkinan = $versi->skalaKemungkinan()->get();
        $dampak = $versi->skalaDampak()->get();
        $tingkat = $versi->tingkatRisiko()->get();
        $matriks = $versi->matriksRisiko()
            ->with(['kemungkinan', 'dampak', 'tingkatRisiko'])
            ->get();

        if ($kemungkinan->count() !== 5 || $dampak->count() !== 5) {
            throw new LogicException(
                'Metode harus memiliki tepat 5 skala kemungkinan dan 5 skala dampak.'
            );
        }

        if ($tingkat->isEmpty()) {
            throw new LogicException(
                'Metode harus memiliki minimal satu tingkat risiko.'
            );
        }

        if ($matriks->count() !== 25) {
            throw new LogicException(
                'Matriks risiko harus memiliki tepat 25 kombinasi.'
            );
        }

        if (
            $kemungkinan->pluck('nilai')->sort()->values()->all() !== [1, 2, 3, 4, 5]
            || $dampak->pluck('nilai')->sort()->values()->all() !== [1, 2, 3, 4, 5]
        ) {
            throw new LogicException(
                'Skala kemungkinan dan dampak harus memiliki nilai lengkap dari 1 sampai 5.'
            );
        }

        $levelIds = $tingkat->pluck('id')->all();
        $combinations = [];

        foreach ($matriks as $row) {
            if (
                ! $row->kemungkinan
                || ! $row->dampak
                || ! $row->tingkatRisiko
                || $row->kemungkinan->versi_metode_id !== $versi->id
                || $row->dampak->versi_metode_id !== $versi->id
                || $row->tingkatRisiko->versi_metode_id !== $versi->id
                || ! in_array($row->tingkat_risiko_id, $levelIds, true)
            ) {
                throw new LogicException(
                    'Matriks memiliki referensi yang tidak valid atau berbeda versi.'
                );
            }

            $key = $row->kemungkinan->nilai.'-'.$row->dampak->nilai;

            if (isset($combinations[$key])) {
                throw new LogicException(
                    'Matriks memiliki kombinasi kemungkinan dan dampak duplikat.'
                );
            }

            $combinations[$key] = true;
        }

        if (count($combinations) !== 25) {
            throw new LogicException(
                'Matriks belum mencakup seluruh 25 kombinasi.'
            );
        }
    }
}
