<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'current_password' => 'password',
            'password' => 'a-new-private-password',
            'password_confirmation' => 'a-new-private-password',
        ];
    }

    public function test_guests_non_admins_and_inactive_admins_cannot_change_passwords(): void
    {
        $this->patchJson('/api/admin/password', $this->payload())->assertUnauthorized();
        $user = User::factory()->create();
        $this->actingAs($user)->patchJson('/api/admin/password', $this->payload())->assertForbidden();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $inactive = User::factory()->admin()->create(['is_active' => false]);
        $this->actingAs($inactive)->patchJson('/api/admin/password', $this->payload())->assertForbidden();
        $this->assertTrue(Hash::check('password', $inactive->fresh()->password));
    }

    public static function invalidPasswords(): array
    {
        return [
            'wrong current password' => [['current_password' => 'incorrect'], 'current_password'],
            'missing current password' => [['current_password' => ''], 'current_password'],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'long password' => [['password' => str_repeat('x', 73), 'password_confirmation' => str_repeat('x', 73)], 'password'],
            'mismatched confirmation' => [['password_confirmation' => 'different-password'], 'password'],
            'missing confirmation' => [['password_confirmation' => ''], 'password'],
            'unchanged password' => [['password' => 'same-current-password', 'password_confirmation' => 'same-current-password', 'current_password' => 'same-current-password'], 'password'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_invalid_password_update_leaves_the_existing_password_unchanged(array $changes, string $field): void
    {
        $current = ($changes['current_password'] ?? '') === 'same-current-password' ? 'same-current-password' : 'password';
        $admin = User::factory()->admin()->create(['password' => $current]);
        $hash = $admin->password;

        $this->actingAs($admin)->patchJson('/api/admin/password', [...$this->payload(), ...$changes])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertSame($hash, $admin->fresh()->password);
    }

    public function test_admin_updates_only_their_own_password_and_can_sign_in_with_the_new_password(): void
    {
        $this->withHeader('Referer', 'http://localhost:5173/admin/account');
        $admin = User::factory()->admin()->create(['email' => 'rylie@example.com']);
        $other = User::factory()->admin()->create();
        $otherHash = $other->password;
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'password'])->assertOk();

        $this->patchJson('/api/admin/password', [...$this->payload(), 'user_id' => $other->id])
            ->assertOk()->assertExactJson(['message' => 'Password updated successfully.']);

        $this->assertTrue(Hash::check('a-new-private-password', $admin->fresh()->password));
        $this->assertSame($otherHash, $other->fresh()->password);
        $this->getJson('/api/auth/me')->assertOk();
        $this->postJson('/api/auth/logout')->assertOk();
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'password'])->assertUnauthorized();
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'a-new-private-password'])->assertOk();
    }
}
