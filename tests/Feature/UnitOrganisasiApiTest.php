<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UnitOrganisasiApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['SUPER_ADMIN', 'KASUB'] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_view_unit_list(): void
    {
        $response = $this->getJson('/api/v1/unit-organisasi');

        $response->assertUnauthorized();
    }

    public function test_kasub_only_sees_units_within_its_scope(): void
    {
        $user = $this->createUser();

        $bidang = $this->createUnit(
            'BIDANG-PARENT',
            'BIDANG'
        );

        $root = $this->createUnit(
            'SUB-ROOT',
            'SUBBIDANG',
            $bidang->id
        );

        $child = $this->createUnit(
            'SUB-CHILD',
            'SUBBAGIAN',
            $root->id
        );

        $sibling = $this->createUnit(
            'SUB-SIBLING',
            'SUBBIDANG',
            $bidang->id
        );

        $this->createScope($user, 'KASUB', $root->id);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/unit-organisasi');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'kode' => 'SUB-ROOT',
            ])
            ->assertJsonFragment([
                'kode' => 'SUB-CHILD',
            ])
            ->assertJsonMissing([
                'kode' => 'BIDANG-PARENT',
            ])
            ->assertJsonMissing([
                'kode' => 'SUB-SIBLING',
            ]);

        $data = collect($response->json('data'))->keyBy('kode');

        // Parent di luar cakupan tidak boleh dibocorkan.
        $this->assertNull($data['SUB-ROOT']['parent']);

        // Children yang berada dalam cakupan tetap terlihat.
        $this->assertSame(
            ['SUB-CHILD'],
            collect($data['SUB-ROOT']['children'])
                ->pluck('kode')
                ->all()
        );
    }

    public function test_super_admin_can_view_all_active_units(): void
    {
        $user = $this->createUser();

        $this->createScope($user, 'SUPER_ADMIN', null);

        $activeUnit = $this->createUnit(
            'UNIT-AKTIF',
            'SATKER'
        );

        $this->createUnit(
            'UNIT-NONAKTIF',
            'SATKER',
            null,
            false
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/unit-organisasi');

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activeUnit->id,
                'kode' => 'UNIT-AKTIF',
            ])
            ->assertJsonMissing([
                'kode' => 'UNIT-NONAKTIF',
            ]);
    }

    public function test_user_without_active_role_cannot_view_unit_list(): void
    {
        $user = $this->createUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/unit-organisasi');

        $response->assertForbidden();
    }

    public function test_user_can_view_unit_detail_within_scope(): void
    {
        $user = $this->createUser();

        $root = $this->createUnit(
            'SUB-ROOT',
            'SUBBIDANG'
        );

        $child = $this->createUnit(
            'SUB-CHILD',
            'SUBBAGIAN',
            $root->id
        );

        $this->createScope($user, 'KASUB', $root->id);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/v1/unit-organisasi/{$root->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.kode', 'SUB-ROOT')
            ->assertJsonPath('data.children.0.id', $child->id)
            ->assertJsonPath('data.children.0.kode', 'SUB-CHILD');
    }

    public function test_user_cannot_view_unit_detail_outside_scope(): void
    {
        $user = $this->createUser();

        $accessibleUnit = $this->createUnit(
            'SUB-ACCESS',
            'SUBBIDANG'
        );

        $outsideUnit = $this->createUnit(
            'SUB-OUTSIDE',
            'SUBBIDANG'
        );

        $this->createScope(
            $user,
            'KASUB',
            $accessibleUnit->id
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/v1/unit-organisasi/{$outsideUnit->id}"
        );

        $response->assertForbidden();
    }

    public function test_user_cannot_view_inactive_unit_detail(): void
    {
        $user = $this->createUser();

        $unit = $this->createUnit(
            'SUB-INACTIVE',
            'SUBBIDANG',
            null,
            false
        );

        $this->createScope($user, 'KASUB', $unit->id);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/v1/unit-organisasi/{$unit->id}"
        );

        $response->assertForbidden();
    }

    private function createUser(): User
    {
        return User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);
    }

    private function createUnit(
        string $kode,
        string $jenis,
        ?int $parentId = null,
        bool $aktif = true
    ): UnitOrganisasi {
        return UnitOrganisasi::create([
            'parent_id' => $parentId,
            'kode' => $kode,
            'nama' => "Unit {$kode}",
            'jenis' => $jenis,
            'aktif' => $aktif,
        ]);
    }

    private function createScope(
        User $user,
        string $roleCode,
        ?int $unitId
    ): UserRoleScope {
        $role = Role::where('kode', $roleCode)->firstOrFail();

        return UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);
    }
}
