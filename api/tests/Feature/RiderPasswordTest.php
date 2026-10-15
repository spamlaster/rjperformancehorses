<?php

namespace Tests\Feature;

use App\Models\Rider;
use App\Models\User;
use App\Services\RiderAccountService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class RiderPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_email_contains_a_link_and_reset_preserves_the_existing_account_and_riders(): void
    {
        Notification::fake();
        $user = app(RiderAccountService::class)->signIn('parent@example.com');
        $user->update(['password' => 'Original-password!123']);
        $customer = app(RiderAccountService::class)->customerFor($user);
        $rider = Rider::create(['customer_id' => $customer->id, 'first_name' => 'Child', 'last_name' => 'Rider', 'date_of_birth' => '2015-01-01']);
        $this->postJson('/api/rider/auth/forgot-password', ['email' => 'PARENT@example.com', 'returnTo' => '/book'])->assertOk();
        $notification = Notification::sent($user, ResetPassword::class)->sole();
        $url = $notification->toMail($user)->actionUrl;
        $this->assertStringContainsString('/rider/reset-password?', $url);
        $this->assertStringContainsString('token='.$notification->token, $url);
        $this->assertTrue(Hash::check('Original-password!123', $user->fresh()->password));
        $payload = ['email' => $user->email, 'token' => $notification->token, 'password' => 'Updated-password!123', 'password_confirmation' => 'Updated-password!123'];
        $this->postJson('/api/rider/auth/reset-password', $payload)->assertOk();
        $this->assertTrue(Hash::check('Updated-password!123', $user->fresh()->password));
        $this->postJson('/api/rider/auth/reset-password', $payload)->assertUnprocessable();
        $this->postJson('/api/rider/auth/login', ['email' => $user->email, 'password' => 'Original-password!123'])->assertUnprocessable();
        $this->postJson('/api/rider/auth/login', ['email' => $user->email, 'password' => 'Updated-password!123'])->assertOk();
        $this->getJson('/api/rider/account')->assertOk()->assertJsonPath('riders.0.id', $rider->id);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_reset_requests_do_not_reveal_unknown_inactive_or_staff_emails_or_change_passwords(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $inactive = User::factory()->create(['is_active' => false]);
        $hash = $admin->password;
        $messages = [];
        foreach ([$admin->email, $inactive->email, 'unknown@example.com'] as $email) {
            $messages[] = $this->postJson('/api/rider/auth/forgot-password', ['email' => $email])->assertOk()->json('message');
        }
        $this->assertCount(1, array_unique($messages));
        Notification::assertNothingSent();
        $token = Password::createToken($admin);
        $this->postJson('/api/rider/auth/reset-password', ['email' => $admin->email, 'token' => $token, 'password' => 'Replacement!123', 'password_confirmation' => 'Replacement!123'])->assertUnprocessable();
        $this->assertSame($hash, $admin->fresh()->password);
        $this->assertDatabaseMissing('users', ['email' => 'unknown@example.com']);
    }

    public function test_reset_rejects_wrong_expired_tokens_and_unconfirmed_passwords(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $token = Password::createToken($user);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'Replacement!123', 'password_confirmation' => 'different'];
        $this->postJson('/api/rider/auth/reset-password', $payload)->assertUnprocessable();
        $payload['password_confirmation'] = $payload['password'];
        $this->postJson('/api/rider/auth/reset-password', [...$payload, 'token' => 'wrong-token'])->assertUnprocessable();
        $this->travel(61)->minutes();
        $this->postJson('/api/rider/auth/reset-password', $payload)->assertUnprocessable();
        $this->assertSame($hash, $user->fresh()->password);
    }
}
