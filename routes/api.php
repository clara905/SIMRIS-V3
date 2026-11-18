<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([
    'auth:sanctum',
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
    'simris.role:ADMIN_SATKER,KASATKER',
])->group(function () {
    Route::get('/administrasi/dashboard', function () {
        return response()->json([
            'message' => 'Akses administrasi berhasil.',
        ]);
    });
});
