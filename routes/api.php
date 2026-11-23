<?php

use App\Http\Controllers\AsetController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\KategoriAsetController;
use App\Http\Controllers\KlasifikasiInformasiController;
use App\Http\Controllers\MetodeRisikoController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PenempatanPegawaiController;
use App\Http\Controllers\PengajuanAkunController;
use App\Http\Controllers\PenilaianRisikoController;
use App\Http\Controllers\PenugasanAsetController;
use App\Http\Controllers\RoleAssignmentController;
use App\Http\Controllers\SumberAsetController;
use App\Http\Controllers\UnitOrganisasiController;
use App\Http\Controllers\UserAdministrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware([
    'auth:sanctum',
    'simris.active',
]);

Route::middleware([
    'auth:sanctum',
    'simris.active',
    'simris.role:SUPER_ADMIN',
])->group(function () {
    Route::get('/super-admin/dashboard', function () {
        return response()->json([
            'message' => 'Akses SUPER_ADMIN berhasil.',
        ]);
    });
});

Route::middleware([
    'auth:sanctum',
    'simris.active',
    'simris.role:ADMIN_SATKER,KASATKER',
])->group(function () {
    Route::get('/administrasi/dashboard', function () {
        return response()->json([
            'message' => 'Akses administrasi berhasil.',
        ]);
    });
});

Route::prefix('v1')
    ->middleware([
        'auth:sanctum',
        'simris.active',
    ])
    ->group(function () {

        // Unit Organisasi
        Route::get(
            '/unit-organisasi',
            [UnitOrganisasiController::class, 'index']
        );

        Route::get(
            '/unit-organisasi/{unit}',
            [UnitOrganisasiController::class, 'show']
        );

        // Data Pegawai
        Route::get(
            '/pegawai',
            [PegawaiController::class, 'index']
        );

        Route::get(
            '/pegawai/{pegawai}',
            [PegawaiController::class, 'show']
        );

        Route::post(
            '/pegawai',
            [PegawaiController::class, 'store']
        );

        Route::put(
            '/pegawai/{pegawai}',
            [PegawaiController::class, 'update']
        );

        Route::patch(
            '/pegawai/{pegawai}/nonaktifkan',
            [PegawaiController::class, 'deactivate']
        );

        // Transfer dan Riwayat Penempatan Pegawai
        Route::patch(
            '/pegawai/{pegawai}/transfer',
            [PenempatanPegawaiController::class, 'transfer']
        );

        Route::get(
            '/pegawai/{pegawai}/riwayat-penempatan',
            [PenempatanPegawaiController::class, 'history']
        );

        Route::post(
            '/administrasi/pengajuan-akun/{pengajuan}/setujui',
            [PengajuanAkunController::class, 'approve']
        );

        Route::post(
            '/administrasi/pengajuan-akun/{pengajuan}/tolak',
            [PengajuanAkunController::class, 'reject']
        );

        // Role dan Penunjukan Admin
        Route::get(
            '/roles',
            [RoleAssignmentController::class, 'roles']
        );

        // Pengajuan Akun
        Route::get(
            '/administrasi/pengajuan-akun',
            [PengajuanAkunController::class, 'index']
        );

        Route::get(
            '/administrasi/pengajuan-akun/{pengajuan}',
            [PengajuanAkunController::class, 'show']
        );

        Route::get(
            '/administrasi/pengguna/{user}/role',
            [RoleAssignmentController::class, 'history']
        );

        Route::get(
            '/risiko/{risiko}/penilaian',
            [PenilaianRisikoController::class, 'index']
        );

        Route::post(
            '/risiko/{risiko}/penilaian',
            [PenilaianRisikoController::class, 'store']
        );

        Route::put(
            '/administrasi/pengguna/{user}/role',
            [RoleAssignmentController::class, 'assign']
        );

        // Master Data Aset

        // Sumber Aset
        Route::get('/sumber-aset', [SumberAsetController::class, 'index']);
        Route::post('/sumber-aset', [SumberAsetController::class, 'store']);
        Route::get('/sumber-aset/{sumberAset}', [SumberAsetController::class, 'show']);
        Route::put('/sumber-aset/{sumberAset}', [SumberAsetController::class, 'update']);
        Route::delete('/sumber-aset/{sumberAset}', [SumberAsetController::class, 'destroy']);

        // Kategori Aset
        Route::get('/kategori-aset', [KategoriAsetController::class, 'index']);
        Route::post('/kategori-aset', [KategoriAsetController::class, 'store']);
        Route::get('/kategori-aset/{kategoriAset}', [KategoriAsetController::class, 'show']);
        Route::put('/kategori-aset/{kategoriAset}', [KategoriAsetController::class, 'update']);
        Route::delete('/kategori-aset/{kategoriAset}', [KategoriAsetController::class, 'destroy']);

        // Klasifikasi Informasi
        Route::get('/klasifikasi-informasi', [KlasifikasiInformasiController::class, 'index']);
        Route::post('/klasifikasi-informasi', [KlasifikasiInformasiController::class, 'store']);
        Route::get('/klasifikasi-informasi/{klasifikasiInformasi}', [KlasifikasiInformasiController::class, 'show']);
        Route::put('/klasifikasi-informasi/{klasifikasiInformasi}', [KlasifikasiInformasiController::class, 'update']);
        Route::delete('/klasifikasi-informasi/{klasifikasiInformasi}', [KlasifikasiInformasiController::class, 'destroy']);

        // Inventaris Aset
        Route::get('/aset', [AsetController::class, 'index']);
        Route::post('/aset', [AsetController::class, 'store']);
        Route::get('/aset/{aset}', [AsetController::class, 'show']);
        Route::put('/aset/{aset}', [AsetController::class, 'update']);
        Route::delete('/aset/{aset}', [AsetController::class, 'destroy']);

        // Penugasan Aset
        Route::get(
            '/aset/{aset}/penugasan',
            [PenugasanAsetController::class, 'index']
        );

        Route::post(
            '/aset/{aset}/penugasan',
            [PenugasanAsetController::class, 'store']
        );

        Route::patch(
            '/penugasan-aset/{penugasan}/akhiri',
            [PenugasanAsetController::class, 'end']
        );

        // Dokumen
        Route::get('/dokumen', [DokumenController::class, 'index']);
        Route::post('/dokumen', [DokumenController::class, 'store']);
        Route::get('/dokumen/{dokumen}', [DokumenController::class, 'show']);
        Route::get('/dokumen/{dokumen}/download', [
            DokumenController::class,
            'download',
        ]);

        // Master Metode Risiko
        Route::get(
            '/metode-risiko',
            [MetodeRisikoController::class, 'index']
        );

        Route::get(
            '/metode-risiko/{versi}',
            [MetodeRisikoController::class, 'show']
        );

        Route::post(
            '/metode-risiko',
            [MetodeRisikoController::class, 'store']
        );

        Route::put(
            '/metode-risiko/{versi}/konfigurasi',
            [MetodeRisikoController::class, 'configure']
        );

        Route::post(
            '/metode-risiko/{versi}/bekukan',
            [MetodeRisikoController::class, 'freeze']
        );

        // Administrasi Pengguna
        Route::patch(
            '/administrasi/pengguna/{user}/nonaktifkan',
            [UserAdministrationController::class, 'deactivate']
        );

        Route::patch(
            '/administrasi/pengguna/{user}/aktifkan-kembali',
            [UserAdministrationController::class, 'reactivate']
        );
    });
