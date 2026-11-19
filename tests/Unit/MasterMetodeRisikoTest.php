<?php

namespace Tests\Unit;

use App\Models\MatriksRisiko;
use App\Models\SkalaDampak;
use App\Models\SkalaKemungkinan;
use App\Models\TingkatRisiko;
use App\Models\VersiMetodeRisiko;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class MasterMetodeRisikoTest extends TestCase
{
    public function test_master_metode_risiko_models_use_correct_tables(): void
    {
        $this->assertSame('versi_metode_risiko', (new VersiMetodeRisiko)->getTable());
        $this->assertSame('skala_kemungkinan', (new SkalaKemungkinan)->getTable());
        $this->assertSame('skala_dampak', (new SkalaDampak)->getTable());
        $this->assertSame('tingkat_risiko', (new TingkatRisiko)->getTable());
        $this->assertSame('matriks_risiko', (new MatriksRisiko)->getTable());
    }

    public function test_master_metode_risiko_models_do_not_use_timestamps(): void
    {
        $models = [
            new VersiMetodeRisiko,
            new SkalaKemungkinan,
            new SkalaDampak,
            new TingkatRisiko,
            new MatriksRisiko,
        ];

        foreach ($models as $model) {
            $this->assertFalse($model->usesTimestamps());
        }
    }

    public function test_versi_metode_has_correct_fillable_and_casts(): void
    {
        $model = new VersiMetodeRisiko;

        $this->assertSame([
            'kode_versi',
            'nama',
            'deskripsi',
            'dibekukan',
            'dibekukan_pada',
        ], $model->getFillable());

        $this->assertSame('boolean', $model->getCasts()['dibekukan']);
        $this->assertSame('datetime', $model->getCasts()['dibekukan_pada']);
    }

    public function test_skala_kemungkinan_has_correct_attributes_and_relations(): void
    {
        $model = new SkalaKemungkinan;

        $this->assertSame([
            'versi_metode_id',
            'nilai',
            'nama',
            'deskripsi',
        ], $model->getFillable());

        $this->assertInstanceOf(BelongsTo::class, $model->versiMetode());
        $this->assertInstanceOf(HasMany::class, $model->matriksRisiko());
    }

    public function test_skala_dampak_has_correct_attributes_and_relations(): void
    {
        $model = new SkalaDampak;

        $this->assertSame([
            'versi_metode_id',
            'nilai',
            'nama',
            'deskripsi',
        ], $model->getFillable());

        $this->assertInstanceOf(BelongsTo::class, $model->versiMetode());
        $this->assertInstanceOf(HasMany::class, $model->matriksRisiko());
    }

    public function test_tingkat_risiko_has_correct_attributes_and_relations(): void
    {
        $model = new TingkatRisiko;

        $this->assertSame([
            'versi_metode_id',
            'kode',
            'nama',
            'urutan',
        ], $model->getFillable());

        $this->assertInstanceOf(BelongsTo::class, $model->versiMetode());
        $this->assertInstanceOf(HasMany::class, $model->matriksRisiko());
    }

    public function test_matriks_risiko_has_correct_attributes_and_relations(): void
    {
        $model = new MatriksRisiko;

        $this->assertSame([
            'versi_metode_id',
            'kemungkinan_id',
            'dampak_id',
            'tingkat_risiko_id',
            'skor',
            'rekomendasi_keputusan',
        ], $model->getFillable());

        $this->assertInstanceOf(BelongsTo::class, $model->versiMetode());
        $this->assertInstanceOf(BelongsTo::class, $model->kemungkinan());
        $this->assertInstanceOf(BelongsTo::class, $model->dampak());
        $this->assertInstanceOf(BelongsTo::class, $model->tingkatRisiko());
    }

    public function test_versi_metode_has_all_master_relations(): void
    {
        $model = new VersiMetodeRisiko;

        $this->assertInstanceOf(HasMany::class, $model->skalaKemungkinan());
        $this->assertInstanceOf(HasMany::class, $model->skalaDampak());
        $this->assertInstanceOf(HasMany::class, $model->tingkatRisiko());
        $this->assertInstanceOf(HasMany::class, $model->matriksRisiko());
    }
}
