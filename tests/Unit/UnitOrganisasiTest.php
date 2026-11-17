<?php

namespace Tests\Unit;

use App\Models\UnitOrganisasi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UnitOrganisasiTest extends TestCase
{
    public function test_unit_organisasi_uses_correct_table(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertSame('unit_organisasi', $unit->getTable());
        $this->assertFalse($unit->usesTimestamps());
    }

    public function test_unit_organisasi_has_correct_fillable_attributes(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertSame([
            'parent_id',
            'kode',
            'nama',
            'jenis',
            'aktif',
        ], $unit->getFillable());
    }

    public function test_unit_organisasi_casts_attributes_correctly(): void
    {
        $unit = new UnitOrganisasi([
            'parent_id' => '10',
            'aktif' => 1,
        ]);

        $this->assertSame(10, $unit->parent_id);
        $this->assertTrue($unit->aktif);
    }

    public function test_unit_organisasi_has_parent_relation(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertInstanceOf(
            BelongsTo::class,
            $unit->parentUnit()
        );
    }

    public function test_unit_organisasi_has_children_relation(): void
    {
        $unit = new UnitOrganisasi;

        $this->assertInstanceOf(
            HasMany::class,
            $unit->children()
        );
    }
}
