<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class UserRoleScopeTest extends TestCase
{
    public function test_user_role_scope_uses_correct_table(): void
    {
        $model = new UserRoleScope;

        $this->assertSame('user_role_scope', $model->getTable());
        $this->assertFalse($model->timestamps);
    }

    public function test_user_role_scope_has_correct_fillable_attributes(): void
    {
        $model = new UserRoleScope;

        $this->assertSame([
            'user_id',
            'role_id',
            'unit_id',
            'mulai',
            'selesai',
        ], $model->getFillable());
    }

    public function test_user_role_scope_casts_attributes_correctly(): void
    {
        $model = new UserRoleScope;

        $casts = $model->getCasts();

        $this->assertSame('integer', $casts['user_id']);
        $this->assertSame('integer', $casts['role_id']);
        $this->assertSame('integer', $casts['unit_id']);
        $this->assertSame('datetime', $casts['mulai']);
        $this->assertSame('datetime', $casts['selesai']);
    }

    public function test_user_role_scope_belongs_to_user(): void
    {
        $relation = (new UserRoleScope)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('user_id', $relation->getForeignKeyName());
        $this->assertSame((new User)->getTable(), $relation->getRelated()->getTable());
    }

    public function test_user_role_scope_belongs_to_role(): void
    {
        $relation = (new UserRoleScope)->role();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('role_id', $relation->getForeignKeyName());
        $this->assertSame((new Role)->getTable(), $relation->getRelated()->getTable());
    }

    public function test_user_role_scope_belongs_to_unit_organisasi(): void
    {
        $relation = (new UserRoleScope)->unit();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('unit_id', $relation->getForeignKeyName());
        $this->assertSame(
            (new UnitOrganisasi)->getTable(),
            $relation->getRelated()->getTable()
        );
    }
}
