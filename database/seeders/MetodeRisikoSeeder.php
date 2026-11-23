<?php

namespace Database\Seeders;

use App\Models\MatriksRisiko;
use App\Models\SkalaDampak;
use App\Models\SkalaKemungkinan;
use App\Models\TingkatRisiko;
use App\Models\VersiMetodeRisiko;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MetodeRisikoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $versi = VersiMetodeRisiko::firstOrCreate(
                ['kode_versi' => 'SIMRIS-MR-001'],
                [
                    'nama' => 'Metode Penilaian Risiko SIMRIS Versi 1',
                    'deskripsi' => 'Metode penilaian risiko berdasarkan kriteria kemungkinan, dampak, matriks evaluasi, dan kriteria penerimaan risiko dari referensi penelitian.',
                    'dibekukan' => false,
                    'dibekukan_pada' => null,
                ]
            );

            if ($versi->dibekukan) {
                throw new RuntimeException(
                    'Seeder dibatalkan: versi metode SIMRIS-MR-001 sudah dibekukan. Buat versi metode baru untuk perubahan data.'
                );
            }

            $versiId = $versi->id;

            $kemungkinan = [
                1 => [
                    'nama' => 'Hampir Tidak Terjadi',
                    'deskripsi' => 'Persentase kemungkinan dalam satu tahun: X ≤ 5%. Frekuensi kejadian dalam satu tahun: X < 2 kali.',
                ],
                2 => [
                    'nama' => 'Jarang Terjadi',
                    'deskripsi' => 'Persentase kemungkinan dalam satu tahun: 5% < X ≤ 10%. Frekuensi kejadian dalam satu tahun: 2 ≤ X ≤ 5 kali.',
                ],
                3 => [
                    'nama' => 'Kadang-kadang Terjadi',
                    'deskripsi' => 'Persentase kemungkinan dalam satu tahun: 10% < X ≤ 20%. Frekuensi kejadian dalam satu tahun: 6 ≤ X ≤ 9 kali.',
                ],
                4 => [
                    'nama' => 'Sering Terjadi',
                    'deskripsi' => 'Persentase kemungkinan dalam satu tahun: 20% < X ≤ 50%. Frekuensi kejadian dalam satu tahun: 10 ≤ X ≤ 12 kali.',
                ],
                5 => [
                    'nama' => 'Hampir Pasti Terjadi',
                    'deskripsi' => 'Persentase kemungkinan dalam satu tahun: X > 50%. Frekuensi kejadian dalam satu tahun: lebih dari 12 kali.',
                ],
            ];

            foreach ($kemungkinan as $nilai => $data) {
                SkalaKemungkinan::updateOrCreate(
                    [
                        'versi_metode_id' => $versiId,
                        'nilai' => $nilai,
                    ],
                    [
                        'nama' => $data['nama'],
                        'deskripsi' => $data['deskripsi'],
                    ]
                );
            }

            $dampak = [
                1 => [
                    'nama' => 'Tidak Signifikan',
                    'deskripsi' => 'Area dampak Kinerja: peningkatan kinerja 20%; penurunan kinerja 20%.',
                ],
                2 => [
                    'nama' => 'Kurang Signifikan',
                    'deskripsi' => 'Area dampak Kinerja: peningkatan atau penurunan kinerja 20% sampai kurang dari 40%.',
                ],
                3 => [
                    'nama' => 'Cukup Signifikan',
                    'deskripsi' => 'Area dampak Kinerja: peningkatan atau penurunan kinerja 40% sampai kurang dari 60%.',
                ],
                4 => [
                    'nama' => 'Signifikan',
                    'deskripsi' => 'Area dampak Kinerja: peningkatan atau penurunan kinerja 60% sampai kurang dari 80%.',
                ],
                5 => [
                    'nama' => 'Sangat Signifikan',
                    'deskripsi' => 'Area dampak Kinerja: peningkatan atau penurunan kinerja 80% atau lebih.',
                ],
            ];

            foreach ($dampak as $nilai => $data) {
                SkalaDampak::updateOrCreate(
                    [
                        'versi_metode_id' => $versiId,
                        'nilai' => $nilai,
                    ],
                    [
                        'nama' => $data['nama'],
                        'deskripsi' => $data['deskripsi'],
                    ]
                );
            }

            $tingkatRisiko = [
                1 => [
                    'kode' => 'SANGAT_RENDAH',
                    'nama' => 'Sangat Rendah',
                ],
                2 => [
                    'kode' => 'RENDAH',
                    'nama' => 'Rendah',
                ],
                3 => [
                    'kode' => 'SEDANG',
                    'nama' => 'Sedang',
                ],
                4 => [
                    'kode' => 'TINGGI',
                    'nama' => 'Tinggi',
                ],
                5 => [
                    'kode' => 'SANGAT_TINGGI',
                    'nama' => 'Sangat Tinggi',
                ],
            ];

            $tingkatIds = [];

            foreach ($tingkatRisiko as $urutan => $data) {
                $tingkat = TingkatRisiko::updateOrCreate(
                    [
                        'versi_metode_id' => $versiId,
                        'kode' => $data['kode'],
                    ],
                    [
                        'nama' => $data['nama'],
                        'urutan' => $urutan,
                    ]
                );

                $tingkatIds[$urutan] = $tingkat->id;
            }

            $kemungkinanIds = SkalaKemungkinan::query()
                ->where('versi_metode_id', $versiId)
                ->pluck('id', 'nilai');

            $dampakIds = SkalaDampak::query()
                ->where('versi_metode_id', $versiId)
                ->pluck('id', 'nilai');

            // Baris = level kemungkinan 1 sampai 5.
            // Kolom = level dampak 1 sampai 5.
            // Nilai mengikuti Tabel 4.10, bukan hasil perkalian.
            $skorMatriks = [
                1 => [1, 3, 5, 8, 20],
                2 => [2, 7, 11, 13, 21],
                3 => [4, 10, 14, 17, 22],
                4 => [6, 12, 16, 19, 24],
                5 => [9, 15, 18, 23, 25],
            ];

            // Tabel 4.11: TERIMA atau MODIFIKASI.
            $keputusanMatriks = [
                1 => ['TERIMA', 'TERIMA', 'TERIMA', 'MODIFIKASI', 'MODIFIKASI'],
                2 => ['TERIMA', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI'],
                3 => ['TERIMA', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI'],
                4 => ['MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI'],
                5 => ['MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI', 'MODIFIKASI'],
            ];

            foreach ($skorMatriks as $levelKemungkinan => $baris) {
                foreach ($baris as $indexDampak => $skor) {
                    $levelDampak = $indexDampak + 1;

                    // Tingkat risiko mengikuti rentang warna pada Tabel 4.10.
                    $urutanTingkatRisiko = match (true) {
                        $skor <= 5 => 1,
                        $skor <= 10 => 2,
                        $skor <= 15 => 3,
                        $skor <= 20 => 4,
                        default => 5,
                    };

                    MatriksRisiko::updateOrCreate(
                        [
                            'versi_metode_id' => $versiId,
                            'kemungkinan_id' => $kemungkinanIds[$levelKemungkinan],
                            'dampak_id' => $dampakIds[$levelDampak],
                        ],
                        [
                            'tingkat_risiko_id' => $tingkatIds[$urutanTingkatRisiko],
                            'skor' => $skor,
                            'rekomendasi_keputusan' => $keputusanMatriks[$levelKemungkinan][$indexDampak],
                        ]
                    );
                }
            }
        });
    }
}
