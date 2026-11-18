<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        // Autentikasi tetap menjadi tanggung jawab auth:sanctum.
        if (! $user instanceof User) {
            return $next($request);
        }

        if (! $user->aktif) {
            abort(403, 'Akun pengguna tidak aktif.');
        }

        // Akun sistem boleh tidak terhubung dengan pegawai.
        // Namun, jika pegawai_id terisi, pegawai wajib aktif.
        if ($user->pegawai_id !== null) {
            $pegawai = $user->pegawai;

            if (! $pegawai || ! $pegawai->aktif) {
                abort(403, 'Data pegawai tidak aktif atau tidak valid.');
            }
        }

        return $next($request);
    }
}
