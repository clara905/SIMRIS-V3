<?php

namespace Tests\Feature;

use App\Models\LogAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_audit_uses_correct_table_without_timestamps(): void
    {
        $audit = new LogAudit;

        $this->assertSame('log_audit', $audit->getTable());
        $this->assertFalse($audit->usesTimestamps());
    }

    public function test_log_audit_belongs_to_actor(): void
    {
        $user = $this->createUser();

        $audit = $this->createAudit($user);

        $this->assertTrue($audit->pelaku->is($user));
        $this->assertSame($user->id, $audit->pelaku()->first()->id);
    }

    public function test_log_audit_can_have_null_actor(): void
    {
        $audit = $this->createAudit();

        $this->assertNull($audit->user_id);
        $this->assertNull($audit->pelaku);
    }

    public function test_correction_belongs_to_original_audit(): void
    {
        $user = $this->createUser();

        $original = $this->createAudit($user);

        $correction = $this->createAudit(
            user: $user,
            attributes: [
                'aksi' => 'KOREKSI_AUDIT',
                'koreksi_dari_id' => $original->id,
                'catatan' => 'Koreksi atas kesalahan pencatatan.',
            ],
        );

        $this->assertTrue($correction->koreksiDari->is($original));
        $this->assertSame(
            $original->id,
            $correction->koreksiDari()->first()->id
        );
    }

    public function test_original_audit_has_many_corrections(): void
    {
        $user = $this->createUser();

        $original = $this->createAudit($user);

        $firstCorrection = $this->createAudit(
            user: $user,
            attributes: [
                'aksi' => 'KOREKSI_AUDIT',
                'koreksi_dari_id' => $original->id,
                'catatan' => 'Koreksi pertama.',
            ],
        );

        $secondCorrection = $this->createAudit(
            user: $user,
            attributes: [
                'aksi' => 'KOREKSI_AUDIT',
                'koreksi_dari_id' => $original->id,
                'catatan' => 'Koreksi kedua.',
            ],
        );

        $corrections = $original->koreksi;

        $this->assertCount(2, $corrections);
        $this->assertTrue(
            $corrections->contains(
                fn (LogAudit $audit) => $audit->is($firstCorrection)
            )
        );
        $this->assertTrue(
            $corrections->contains(
                fn (LogAudit $audit) => $audit->is($secondCorrection)
            )
        );
    }

    public function test_audit_json_data_is_cast_to_array(): void
    {
        $oldData = [
            'aktif' => false,
            'email' => 'lama@example.com',
        ];

        $newData = [
            'aktif' => true,
            'email' => 'baru@example.com',
        ];

        $audit = $this->createAudit(
            attributes: [
                'data_lama' => $oldData,
                'data_baru' => $newData,
            ],
        );

        $audit->refresh();

        $this->assertSame($oldData, $audit->data_lama);
        $this->assertSame($newData, $audit->data_baru);
        $this->assertIsArray($audit->data_lama);
        $this->assertIsArray($audit->data_baru);
    }

    public function test_actor_snapshot_is_stored_correctly(): void
    {
        $user = $this->createUser();

        $audit = $this->createAudit(
            user: $user,
            attributes: [
                'pelaku_nama' => 'Pegawai Pengujian',
                'pelaku_username' => 'penguji.simris',
            ],
        );

        $audit->refresh();

        $this->assertSame('Pegawai Pengujian', $audit->pelaku_nama);
        $this->assertSame('penguji.simris', $audit->pelaku_username);
        $this->assertSame($user->id, $audit->user_id);
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

    public function test_existing_audit_cannot_be_updated(): void
    {
        $audit = $this->createAudit();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Log audit bersifat immutable dan tidak dapat diubah.'
        );

        $audit->update([
            'catatan' => 'Mencoba mengubah log asli.',
        ]);
    }

    public function test_existing_audit_cannot_be_deleted(): void
    {
        $audit = $this->createAudit();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Log audit bersifat immutable dan tidak dapat dihapus.'
        );

        $audit->delete();
    }

    private function createAudit(
        ?User $user = null,
        array $attributes = [],
    ): LogAudit {
        return LogAudit::create(array_merge([
            'user_id' => $user?->id,
            'pelaku_nama' => $user?->username,
            'pelaku_username' => $user?->username,
            'aksi' => 'UPDATE',
            'entitas' => 'users',
            'entitas_id' => $user?->id,
            'data_lama' => null,
            'data_baru' => null,
            'ip_address' => '127.0.0.1',
            'koreksi_dari_id' => null,
            'catatan' => null,
            'waktu' => now(),
        ], $attributes));
    }
}
