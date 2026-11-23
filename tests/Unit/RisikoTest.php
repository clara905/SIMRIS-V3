<?php

namespace Tests\Unit;

use App\Models\Ancaman;
use App\Models\AsesmenRisiko;
use App\Models\Kerentanan;
use App\Models\Pegawai;
use App\Models\Risiko;
use App\Models\SumberAncaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RisikoTest extends TestCase
{
    #[Test]
    public function risiko_uses_correct_table_without_timestamps(): void
    {
        $risiko = new Risiko;

        $this->assertSame('risiko', $risiko->getTable());
        $this->assertFalse($risiko->usesTimestamps());
    }

    #[Test]
    public function risiko_has_expected_fillable_attributes(): void
    {
        $this->assertSame([
            'asesmen_id',
            'ancaman_id',
            'sumber_ancaman_id',
            'kerentanan_id',
            'pemilik_risiko_id',
            'dibuat_oleh',
            'kode',
            'skenario',
            'uraian_kerentanan',
            'uraian_dampak',
            'arah_dampak',
            'dibuat_pada',
        ], (new Risiko)->getFillable());
    }

    #[Test]
    public function risiko_casts_attributes_correctly(): void
    {
        $casts = (new Risiko)->getCasts();

        $this->assertSame('integer', $casts['asesmen_id']);
        $this->assertSame('integer', $casts['ancaman_id']);
        $this->assertSame('integer', $casts['sumber_ancaman_id']);
        $this->assertSame('integer', $casts['kerentanan_id']);
        $this->assertSame('integer', $casts['pemilik_risiko_id']);
        $this->assertSame('integer', $casts['dibuat_oleh']);
        $this->assertSame('boolean', $casts['arah_dampak']);
        $this->assertSame('datetime', $casts['dibuat_pada']);
    }

    #[Test]
    public function risiko_belongs_to_its_assessment(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->asesmen()
        );

        $this->assertInstanceOf(
            AsesmenRisiko::class,
            (new Risiko)->asesmen()->getRelated()
        );
    }

    #[Test]
    public function risiko_belongs_to_its_threat(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->ancaman()
        );

        $this->assertInstanceOf(
            Ancaman::class,
            (new Risiko)->ancaman()->getRelated()
        );
    }

    #[Test]
    public function risiko_belongs_to_its_threat_source(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->sumberAncaman()
        );

        $this->assertInstanceOf(
            SumberAncaman::class,
            (new Risiko)->sumberAncaman()->getRelated()
        );
    }

    #[Test]
    public function risiko_belongs_to_its_vulnerability(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->kerentanan()
        );

        $this->assertInstanceOf(
            Kerentanan::class,
            (new Risiko)->kerentanan()->getRelated()
        );
    }

    #[Test]
    public function risiko_belongs_to_its_risk_owner(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->pemilikRisiko()
        );

        $this->assertInstanceOf(
            Pegawai::class,
            (new Risiko)->pemilikRisiko()->getRelated()
        );
    }

    #[Test]
    public function risiko_belongs_to_its_creator(): void
    {
        $this->assertInstanceOf(
            BelongsTo::class,
            (new Risiko)->pembuat()
        );

        $this->assertInstanceOf(
            User::class,
            (new Risiko)->pembuat()->getRelated()
        );
    }

    #[Test]
    public function sumber_ancaman_uses_correct_table_without_timestamps(): void
    {
        $sumber = new SumberAncaman;

        $this->assertSame('sumber_ancaman', $sumber->getTable());
        $this->assertFalse($sumber->usesTimestamps());
        $this->assertSame(['kode', 'nama'], $sumber->getFillable());
    }
}
