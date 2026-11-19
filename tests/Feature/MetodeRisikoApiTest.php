<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\UnitOrganisasi;
use App\Models\User;
use App\Models\UserRoleScope;
use App\Models\VersiMetodeRisiko;
use App\Services\MetodeRisikoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetodeRisikoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $code) {
            Role::create([
                'kode' => $code,
                'nama' => $code,
            ]);
        }
    }

    public function test_unauthenticated_user_cannot_access_method_api(): void
    {
        $this->getJson('/api/v1/metode-risiko')
            ->assertUnauthorized();

        $this->postJson('/api/v1/metode-risiko', [
            'kode_versi' => 'METODE-01',
            'nama' => 'Metode Pengujian',
        ])->assertUnauthorized();
    }

    public function test_all_active_roles_can_read_method_list_and_detail(): void
    {
        $versi = $this->createDraft();

        foreach ([
            'SUPER_ADMIN',
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);

            Sanctum::actingAs($user);

            $this->getJson('/api/v1/metode-risiko')
                ->assertOk()
                ->assertJsonFragment([
                    'kode_versi' => 'METODE-API-01',
                ]);

            $this->getJson("/api/v1/metode-risiko/{$versi->id}")
                ->assertOk()
                ->assertJsonPath('data.kode_versi', 'METODE-API-01');
        }
    }

    public function test_only_super_admin_can_create_method_draft(): void
    {
        $superAdmin = $this->createUserWithRole('SUPER_ADMIN');

        Sanctum::actingAs($superAdmin);

        $this->postJson('/api/v1/metode-risiko', [
            'kode_versi' => 'METODE-BARU',
            'nama' => 'Metode Risiko Baru',
            'deskripsi' => 'Draft metode risiko.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.kode_versi', 'METODE-BARU')
            ->assertJsonPath('data.dibekukan', false);

        $this->assertDatabaseHas('versi_metode_risiko', [
            'kode_versi' => 'METODE-BARU',
            'dibekukan' => false,
        ]);
    }

    public function test_non_super_admin_cannot_create_method_draft(): void
    {
        foreach ([
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);

            Sanctum::actingAs($user);

            $this->postJson('/api/v1/metode-risiko', [
                'kode_versi' => 'METODE-'.$roleCode,
                'nama' => 'Metode '.$roleCode,
            ])->assertForbidden();
        }

        $this->assertDatabaseCount('versi_metode_risiko', 0);
    }

    public function test_super_admin_can_configure_draft_method(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $versi = $this->createDraft();

        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/metode-risiko/{$versi->id}/konfigurasi",
            $this->validConfiguration()
        )
            ->assertOk()
            ->assertJsonPath('data.dibekukan', false)
            ->assertJsonCount(5, 'data.skala_kemungkinan')
            ->assertJsonCount(5, 'data.skala_dampak')
            ->assertJsonCount(3, 'data.tingkat_risiko')
            ->assertJsonCount(25, 'data.matriks');
    }

    public function test_non_super_admin_cannot_configure_draft_method(): void
    {
        $versi = $this->createDraft();

        foreach ([
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);

            Sanctum::actingAs($user);

            $this->putJson(
                "/api/v1/metode-risiko/{$versi->id}/konfigurasi",
                $this->validConfiguration()
            )->assertForbidden();
        }

        $this->assertDatabaseCount('skala_kemungkinan', 0);
        $this->assertDatabaseCount('skala_dampak', 0);
        $this->assertDatabaseCount('matriks_risiko', 0);
    }

    public function test_super_admin_can_freeze_complete_method(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $versi = $this->createDraft();

        $this->app->make(MetodeRisikoService::class)->configureDraft(
            $versi,
            $this->validConfiguration()
        );

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/metode-risiko/{$versi->id}/bekukan"
        )
            ->assertOk()
            ->assertJsonPath('data.dibekukan', true);

        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $versi->id,
            'dibekukan' => true,
        ]);
    }

    public function test_non_super_admin_cannot_freeze_method(): void
    {
        $versi = $this->createDraft();

        foreach ([
            'ADMIN_SATKER',
            'KASATKER',
            'KABID',
            'KASUB',
            'STAFF',
        ] as $roleCode) {
            $user = $this->createUserWithRole($roleCode);

            Sanctum::actingAs($user);

            $this->postJson(
                "/api/v1/metode-risiko/{$versi->id}/bekukan"
            )->assertForbidden();
        }

        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $versi->id,
            'dibekukan' => false,
        ]);
    }

    public function test_frozen_method_cannot_be_configured_again_through_api(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $versi = $this->createDraft();

        $service = $this->app->make(MetodeRisikoService::class);

        $service->configureDraft(
            $versi,
            $this->validConfiguration()
        );

        $frozen = $service->freeze($versi);

        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/metode-risiko/{$frozen->id}/konfigurasi",
            $this->validConfiguration()
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Metode risiko sudah dibekukan dan tidak dapat diubah.'
            );

        $this->assertDatabaseHas('versi_metode_risiko', [
            'id' => $frozen->id,
            'dibekukan' => true,
        ]);
    }

    public function test_incomplete_configuration_cannot_be_frozen_through_api(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $versi = $this->createDraft();

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/metode-risiko/{$versi->id}/bekukan"
        )
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Metode harus memiliki tepat 5 skala kemungkinan dan 5 skala dampak.'
            );
    }

    public function test_invalid_matrix_configuration_returns_validation_error(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $versi = $this->createDraft();

        $configuration = $this->validConfiguration();
        array_pop($configuration['matriks']);

        Sanctum::actingAs($user);

        $this->putJson(
            "/api/v1/metode-risiko/{$versi->id}/konfigurasi",
            $configuration
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('matriks');

        $this->assertDatabaseCount('matriks_risiko', 0);
    }

    public function test_duplicate_method_version_code_is_rejected(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');
        $this->createDraft();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/metode-risiko', [
            'kode_versi' => 'METODE-API-01',
            'nama' => 'Metode Duplikat',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kode_versi');
    }

    public function test_user_without_active_role_cannot_access_method_api(): void
    {
        $user = User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/metode-risiko')
            ->assertForbidden();
    }

    public function test_inactive_user_cannot_access_method_api(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');

        $user->forceFill([
            'aktif' => false,
        ])->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/metode-risiko')
            ->assertForbidden();
    }

    public function test_super_admin_with_unit_scope_cannot_manage_method(): void
    {
        $user = $this->createUserWithRole('SUPER_ADMIN');

        $unit = UnitOrganisasi::create([
            'parent_id' => null,
            'kode' => 'UNIT-SUPER-TEST',
            'nama' => 'Unit Tidak Valid untuk Super Admin',
            'jenis' => 'SATKER',
            'aktif' => true,
        ]);

        $scope = $user->roleScopes()->firstOrFail();

        $scope->forceFill([
            'unit_id' => $unit->id,
        ])->save();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/metode-risiko', [
            'kode_versi' => 'METODE-INVALID-SCOPE',
            'nama' => 'Metode Tidak Valid',
        ])->assertForbidden();
    }

    private function createDraft(): VersiMetodeRisiko
    {
        return app(MetodeRisikoService::class)->createDraft([
            'kode_versi' => 'METODE-API-01',
            'nama' => 'Metode Risiko API',
            'deskripsi' => 'Draft untuk pengujian API.',
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $user = User::create([
            'pegawai_id' => null,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => true,
        ]);

        $unitId = null;

        if ($roleCode !== 'SUPER_ADMIN') {
            $unit = UnitOrganisasi::create([
                'parent_id' => null,
                'kode' => fake()->unique()->bothify('UNIT-####'),
                'nama' => 'Unit Pengujian',
                'jenis' => match ($roleCode) {
                    'KASATKER' => 'SATKER',
                    'KABID' => 'BIDANG',
                    'KASUB' => 'SUBBIDANG',
                    default => 'SUBBAGIAN',
                },
                'aktif' => true,
            ]);

            $unitId = $unit->id;
        }

        $role = Role::where('kode', $roleCode)->firstOrFail();

        UserRoleScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'unit_id' => $unitId,
            'mulai' => now()->subDay(),
            'selesai' => null,
        ]);

        return $user;
    }

    private function validConfiguration(): array
    {
        $kemungkinan = [];
        $dampak = [];

        for ($nilai = 1; $nilai <= 5; $nilai++) {
            $kemungkinan[] = [
                'nilai' => $nilai,
                'nama' => 'Kemungkinan '.$nilai,
                'deskripsi' => 'Deskripsi kemungkinan '.$nilai,
            ];

            $dampak[] = [
                'nilai' => $nilai,
                'nama' => 'Dampak '.$nilai,
                'deskripsi' => 'Deskripsi dampak '.$nilai,
            ];
        }

        $matriks = [];

        for ($k = 1; $k <= 5; $k++) {
            for ($d = 1; $d <= 5; $d++) {
                $matriks[] = [
                    'kemungkinan_nilai' => $k,
                    'dampak_nilai' => $d,
                    'tingkat_risiko_kode' => $this->levelCode($k + $d),
                    'skor' => ($k * 10) + $d,
                    'rekomendasi_keputusan' => 'Rekomendasi '.$k.'-'.$d,
                ];
            }
        }

        return [
            'kemungkinan' => $kemungkinan,
            'dampak' => $dampak,
            'tingkat_risiko' => [
                [
                    'kode' => 'RENDAH',
                    'nama' => 'Rendah',
                    'urutan' => 1,
                ],
                [
                    'kode' => 'SEDANG',
                    'nama' => 'Sedang',
                    'urutan' => 2,
                ],
                [
                    'kode' => 'TINGGI',
                    'nama' => 'Tinggi',
                    'urutan' => 3,
                ],
            ],
            'matriks' => $matriks,
        ];
    }

    private function levelCode(int $sum): string
    {
        return match (true) {
            $sum <= 4 => 'RENDAH',
            $sum <= 7 => 'SEDANG',
            default => 'TINGGI',
        };
    }
}
