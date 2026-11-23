<?php

namespace Tests\Unit;

use App\Models\AsesmenRisiko;
use App\Models\PeriodePenilaian;
use App\Models\SeriAsesmen;
use App\Models\UnitOrganisasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class PeriodeAsesmenTest extends TestCase
{
    public function test_models_use_correct_tables_without_timestamps(): void
    {
        $periode = new PeriodePenilaian;
        $seri = new SeriAsesmen;
        $asesmen = new AsesmenRisiko;

        $this->assertSame('periode_penilaian', $periode->getTable());
        $this->assertSame('seri_asesmen', $seri->getTable());
        $this->assertSame('asesmen_risiko', $asesmen->getTable());

        $this->assertFalse($periode->timestamps);
        $this->assertFalse($seri->timestamps);
        $this->assertFalse($asesmen->timestamps);
    }

    public function test_periode_penilaian_has_correct_attributes_and_casts(): void
    {
        $periode = new PeriodePenilaian;

        $this->assertEqualsCanonicalizing([
            'unit_id',
            'dibuat_oleh',
            'nama_periode',
            'tanggal_mulai',
            'tanggal_selesai',
            'status',
            'dibuat_pada',
            'diperbarui_pada',
        ], $periode->getFillable());

        $this->assertSame('integer', $periode->getCasts()['unit_id']);
        $this->assertSame('integer', $periode->getCasts()['dibuat_oleh']);
        $this->assertSame('date', $periode->getCasts()['tanggal_mulai']);
        $this->assertSame('date', $periode->getCasts()['tanggal_selesai']);
        $this->assertSame('datetime', $periode->getCasts()['dibuat_pada']);
    }

    public function test_seri_asesmen_has_correct_attributes(): void
    {
        $seri = new SeriAsesmen;

        $this->assertSame(
            ['kode', 'nama'],
            $seri->getFillable()
        );
    }

    public function test_asesmen_risiko_has_correct_attributes_and_casts(): void
    {
        $asesmen = new AsesmenRisiko;

        $this->assertEqualsCanonicalizing([
            'seri_asesmen_id',
            'versi_sebelumnya_id',
            'periode_id',
            'unit_id',
            'versi_metode_id',
            'dibuat_oleh',
            'tujuan',
            'ruang_lingkup',
            'status',
            'nomor_versi',
            'dibuat_pada',
            'dibekukan_pada',
        ], $asesmen->getFillable());

        $this->assertSame('integer', $asesmen->getCasts()['seri_asesmen_id']);
        $this->assertSame('integer', $asesmen->getCasts()['versi_sebelumnya_id']);
        $this->assertSame('integer', $asesmen->getCasts()['periode_id']);
        $this->assertSame('integer', $asesmen->getCasts()['unit_id']);
        $this->assertSame('integer', $asesmen->getCasts()['versi_metode_id']);
        $this->assertSame('integer', $asesmen->getCasts()['nomor_versi']);
        $this->assertSame('datetime', $asesmen->getCasts()['dibuat_pada']);
        $this->assertSame('datetime', $asesmen->getCasts()['dibekukan_pada']);
    }

    public function test_periode_penilaian_relations_are_correct(): void
    {
        $periode = new PeriodePenilaian;

        $this->assertInstanceOf(BelongsTo::class, $periode->unit());
        $this->assertInstanceOf(BelongsTo::class, $periode->pembuat());
        $this->assertInstanceOf(HasMany::class, $periode->asesmen());
    }

    public function test_seri_asesmen_has_many_assessments(): void
    {
        $seri = new SeriAsesmen;

        $this->assertInstanceOf(HasMany::class, $seri->asesmen());
    }

    public function test_asesmen_risiko_relations_are_correct(): void
    {
        $asesmen = new AsesmenRisiko;

        $this->assertInstanceOf(BelongsTo::class, $asesmen->seri());
        $this->assertInstanceOf(BelongsTo::class, $asesmen->periode());
        $this->assertInstanceOf(BelongsTo::class, $asesmen->unit());
        $this->assertInstanceOf(BelongsTo::class, $asesmen->versiMetode());
        $this->assertInstanceOf(BelongsTo::class, $asesmen->pembuat());
        $this->assertInstanceOf(BelongsTo::class, $asesmen->versiSebelumnya());
        $this->assertInstanceOf(HasMany::class, $asesmen->revisiBerikutnya());
    }

    public function test_unit_organisasi_has_assessment_relations(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertInstanceOf(HasMany::class, $unit->periodePenilaian());
        $this->assertInstanceOf(HasMany::class, $unit->asesmenRisiko());
    }

    public function test_user_has_created_assessment_relations(): void
    {
        $user = new User;

        $this->assertInstanceOf(HasMany::class, $user->periodePenilaianDibuat());
        $this->assertInstanceOf(HasMany::class, $user->asesmenRisikoDibuat());
    }
}
