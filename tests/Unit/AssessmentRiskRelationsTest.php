<?php

namespace Tests\Unit;

use App\Models\AsesmenRisiko;
use App\Models\MatriksRisiko;
use App\Models\PenilaianRisiko;
use App\Models\Risiko;
use App\Models\User;
use App\Models\VersiMetodeRisiko;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssessmentRiskRelationsTest extends TestCase
{
    #[Test]
    public function asesmen_has_many_risiko(): void
    {
        $relation = (new AsesmenRisiko)->risiko();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Risiko::class, $relation->getRelated());
        $this->assertSame('asesmen_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function versi_metode_has_many_asesmen(): void
    {
        $relation = (new VersiMetodeRisiko)->asesmenRisiko();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(AsesmenRisiko::class, $relation->getRelated());
        $this->assertSame('versi_metode_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function penilaian_risiko_belongs_to_risiko(): void
    {
        $relation = (new PenilaianRisiko)->risiko();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Risiko::class, $relation->getRelated());
        $this->assertSame('risiko_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function penilaian_risiko_belongs_to_matriks(): void
    {
        $relation = (new PenilaianRisiko)->matriks();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(MatriksRisiko::class, $relation->getRelated());
        $this->assertSame('matriks_id', $relation->getForeignKeyName());
    }

    #[Test]
    public function penilaian_risiko_belongs_to_penilai(): void
    {
        $relation = (new PenilaianRisiko)->penilai();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('dinilai_oleh', $relation->getForeignKeyName());
    }

    #[Test]
    public function penilaian_risiko_uses_correct_table_and_attributes(): void
    {
        $model = new PenilaianRisiko;

        $this->assertSame('penilaian_risiko', $model->getTable());
        $this->assertFalse($model->usesTimestamps());

        $this->assertSame([
            'risiko_id',
            'matriks_id',
            'dinilai_oleh',
            'jenis_penilaian',
            'alasan_kemungkinan',
            'alasan_dampak',
            'kesimpulan',
            'dinilai_pada',
        ], $model->getFillable());

        $casts = $model->getCasts();

        $this->assertSame('integer', $casts['risiko_id']);
        $this->assertSame('integer', $casts['matriks_id']);
        $this->assertSame('integer', $casts['dinilai_oleh']);
        $this->assertSame('datetime', $casts['dinilai_pada']);
    }
}
