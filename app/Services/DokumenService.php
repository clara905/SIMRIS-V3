<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DokumenService
{
    public function upload(
        User $user,
        UnitOrganisasi $unit,
        UploadedFile $file,
        string $judul,
        string $jenis
    ): Dokumen {
        $disk = Storage::disk('local');

        $extension = $file->extension();

        if (! $extension) {
            throw new RuntimeException(
                'Ekstensi file tidak dapat ditentukan.'
            );
        }

        $uuid = (string) Str::uuid();
        $filename = $uuid.'.'.$extension;
        $directory = 'dokumen/'.$unit->id;
        $storageKey = $directory.'/'.$filename;

        $storedPath = $disk->putFileAs(
            $directory,
            $file,
            $filename
        );

        if (! $storedPath || ! $disk->exists($storageKey)) {
            throw new RuntimeException(
                'File gagal disimpan ke penyimpanan privat.'
            );
        }

        try {
            return DB::transaction(function () use (
                $user,
                $unit,
                $file,
                $judul,
                $jenis,
                $extension,
                $storageKey
            ): Dokumen {
                $dokumen = Dokumen::query()->create([
                    'unit_pemilik_id' => $unit->id,
                    'dibuat_oleh' => $user->id,
                    'kode' => 'DOC-'.Str::upper((string) Str::uuid()),
                    'judul' => $judul,
                    'jenis' => $jenis,
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'storage_key' => $storageKey,
                    'ekstensi' => $extension,
                    'mime_type' => $file->getMimeType(),
                    'ukuran_byte' => $file->getSize(),
                    'checksum_sha256' => hash_file(
                        'sha256',
                        $file->getRealPath()
                    ),
                    'status_pemeriksaan' => 'MENUNGGU_PEMERIKSAAN',
                    'dibuat_pada' => now(),
                ]);

                app(AuditService::class)->record(
                    'UPLOAD_DOKUMEN',
                    'dokumen',
                    (int) $dokumen->id,
                    null,
                    [
                        'kode' => $dokumen->kode,
                        'unit_pemilik_id' => $unit->id,
                        'nama_file_asli' => $dokumen->nama_file_asli,
                        'checksum_sha256' => $dokumen->checksum_sha256,
                    ],
                    'Dokumen diunggah ke penyimpanan privat.'
                );

                return $dokumen->load([
                    'unitPemilik',
                    'pembuat',
                ]);
            });
        } catch (Throwable $exception) {
            $disk->delete($storageKey);

            throw $exception;
        }
    }

    public function download(Dokumen $dokumen)
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($dokumen->storage_key)) {
            abort(404, 'File dokumen tidak ditemukan.');
        }

        return $disk->download(
            $dokumen->storage_key,
            $dokumen->nama_file_asli,
            [
                'Content-Type' => $dokumen->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }
}
