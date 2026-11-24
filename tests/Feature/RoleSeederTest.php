<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_rolls_back_partial_inserts_when_a_role_insert_fails(): void
    {
        DB::unprepared("
            CREATE TRIGGER fail_role_seed
            BEFORE INSERT ON roles
            WHEN NEW.kode = 'STAFF'
            BEGIN
                SELECT RAISE(FAIL, 'role seeder failure');
            END
        ");

        try {
            try {
                $this->seed(RoleSeeder::class);
                $this->fail('The role seeder should fail on the STAFF row.');
            } catch (QueryException) {
                $this->assertDatabaseCount('roles', 0);
            }
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS fail_role_seed');
        }
    }
}
