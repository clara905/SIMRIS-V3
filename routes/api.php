<?php

use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\UnitOrganisasiController;
use App\Http\Controllers\UserAdministrationController;
use App\Http\Controllers\PenempatanPegawaiController;
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
