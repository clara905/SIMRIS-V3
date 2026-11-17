<?php

namespace Tests\Unit;

use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_uses_correct_database_table(): void
    {
        $user = new User;

        $this->assertSame('users', $user->getTable());
        $this->assertFalse($user->usesTimestamps());
    }

    public function test_user_has_correct_fillable_attributes(): void
    {
        $user = new User;

        $this->assertSame([
            'pegawai_id',
            'username',
            'email',
            'firebase_uid',
            'aktif',
        ], $user->getFillable());
    }

    public function test_user_casts_attributes_correctly(): void
    {
        $user = new User([
            'pegawai_id' => '15',
            'aktif' => 1,
        ]);

        $this->assertSame(15, $user->pegawai_id);
        $this->assertTrue($user->aktif);
    }

    public function test_firebase_uid_is_hidden_from_json(): void
    {
        $user = new User([
            'username' => '123456789',
            'email' => 'pegawai@example.com',
            'firebase_uid' => 'firebase-test-uid',
            'aktif' => true,
        ]);

        $data = $user->toArray();

        $this->assertArrayNotHasKey('firebase_uid', $data);
        $this->assertSame('pegawai@example.com', $data['email']);
        $this->assertSame('123456789', $data['username']);
    }

    public function test_user_uses_sanctum_api_tokens(): void
    {
        $traits = class_uses_recursive(User::class);

        $this->assertContains(HasApiTokens::class, $traits);
    }
}
