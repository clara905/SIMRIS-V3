<?php

namespace App\Providers;

use App\Models\Aset;
use App\Models\Dokumen;
use App\Models\KategoriAset;
use App\Models\KlasifikasiInformasi;
use App\Models\PenugasanAset;
use App\Models\SumberAset;
use App\Models\VersiMetodeRisiko;
use App\Policies\AsetPolicy;
use App\Policies\DokumenPolicy;
use App\Policies\KategoriAsetPolicy;
use App\Policies\KlasifikasiInformasiPolicy;
use App\Policies\MetodeRisikoPolicy;
use App\Policies\PenugasanAsetPolicy;
use App\Policies\SumberAsetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Aset::class, AsetPolicy::class);
        Gate::policy(Dokumen::class, DokumenPolicy::class);
        Gate::policy(SumberAset::class, SumberAsetPolicy::class);
        Gate::policy(KategoriAset::class, KategoriAsetPolicy::class);

        Gate::policy(
            KlasifikasiInformasi::class,
            KlasifikasiInformasiPolicy::class
        );

        Gate::policy(
            PenugasanAset::class,
            PenugasanAsetPolicy::class
        );

        Gate::policy(
            VersiMetodeRisiko::class,
            MetodeRisikoPolicy::class
        );
    }
}
