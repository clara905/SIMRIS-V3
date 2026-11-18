<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureActiveUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([
            'auth:sanctum',
            'simris.active',
        ])->get('/_test/active-access', function () {
            return response()->json([
                'message' => 'Berhasil.',
            ]);
        });
    }

    public function test_active_user_can_access_route(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/active-access')
            ->assertOk()
            ->assertJson([
                'message' => 'Berhasil.',
            ]);
    }

    public function test_inactive_user_is_forbidden(): void
    {
        $user = $this->createUser(aktif: false);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/active-access')
            ->assertForbidden();
    }

    public function test_user_with_inactive_employee_is_forbidden(): void
    {
        $pegawai = $this->createPegawai(aktif: false);
        $user = $this->createUser(pegawaiId: $pegawai->id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/active-access')
            ->assertForbidden();
    }

    public function test_user_without_employee_can_access_route(): void
    {
        $user = $this->createUser();

        $this->assertNull($user->pegawai_id);

        $this->actingAs($user, 'sanctum')
            ->getJson('/_test/active-access')
            ->assertOk();
    }

    public function test_unauthenticated_user_is_unauthorized(): void
    {
        $this->getJson('/_test/active-access')
            ->assertUnauthorized();
    }

    private function createUser(
        ?int $pegawaiId = null,
        bool $aktif = true
    ): User {
        return User::create([
            'pegawai_id' => $pegawaiId,
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => fake()->unique()->uuid(),
            'aktif' => $aktif,
        ]);
    }

    private function createPegawai(bool $aktif = true): Pegawai
    {
        return Pegawai::create([
            'nip' => fake()->unique()->numerify('##################'),
            'nama' => fake()->name(),
            'email_kontak' => fake()->safeEmail(),
            'jenis_kepegawaian' => 'PNS',
            'aktif' => $aktif,
        ]);
    }
}
