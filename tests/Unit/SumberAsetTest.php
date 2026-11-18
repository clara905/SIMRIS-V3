<?php

namespace Tests\Unit;

use App\Models\SumberAset;
use PHPUnit\Framework\TestCase;

class SumberAsetTest extends TestCase
{
    public function test_sumber_aset_uses_correct_table(): void
    {
        $this->assertSame('sumber_aset', (new SumberAset)->getTable());
    }

    public function test_sumber_aset_has_correct_fillable_attributes(): void
    {
        $this->assertSame(
            ['kode', 'nama'],
            (new SumberAset)->getFillable()
        );
    }

    public function test_sumber_aset_does_not_use_timestamps(): void
    {
        $this->assertFalse((new SumberAset)->usesTimestamps());
    }
}
