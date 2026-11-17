<?php

namespace Tests\Unit;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function test_role_uses_correct_database_table(): void
    {
        $role = new Role;

        $this->assertSame('roles', $role->getTable());
        $this->assertFalse($role->usesTimestamps());
    }

    public function test_role_has_correct_fillable_attributes(): void
    {
        $role = new Role;

        $this->assertSame([
            'kode',
            'nama',
        ], $role->getFillable());
    }

    public function test_role_has_user_role_scopes_relation(): void
    {
        $role = new Role;

        $this->assertInstanceOf(
            HasMany::class,
            $role->userRoleScopes()
        );
    }

    public function test_role_accepts_all_defined_role_codes(): void
    {
        $roleCodes = [
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ];

        foreach ($roleCodes as $code) {
            $role = new Role([
                'kode' => $code,
            ]);

            $this->assertSame($code, $role->kode);
        }
    }
}
