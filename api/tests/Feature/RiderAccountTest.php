<?php

namespace Tests\Feature;

use App\Mail\RiderSignInCode;
use App\Models\Rider;
use App\Models\User;
use App\Services\RiderAccountService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as ProviderUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

class RiderAccountTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('x', 32))]);
        $this->buildSchedule();
    }

    private function requestCode(string $email = 'rider@example.com'): string
    {
        Mail::fake();
        $this->postJson('/api/rider/auth/email', ['email' => $email])->assertOk();
        Mail::assertSent(RiderSignInCode::class);

        return Mail::sent(RiderSignInCode::class)->last()->code;
    }

    private function riderUser(string $email = 'rider@example.com'): User
    {
        return app(RiderAccountService::class)->signIn($email);
    }

    public function test_email_code_creates_one_verified_account_and_reuses_existing_customer_and_riders(): void
    {
        Rider::create(['customer_id' => 'test-customer', 'first_name' => 'Existing', 'last_name' => 'Rider', 'date_of_birth' => '2010-01-01']);
        $code = $this->requestCode('TEST@example.com');
        $this->assertNotSame($code, DB::table('rider_sign_in_codes')->value('code_hash'));

        $this->postJson('/api/rider/auth/verify', ['email' => 'test@example.com', 'code' => $code])->assertOk();
        $this->getJson('/api/rider/account')->assertOk()->assertJsonPath('customer.id', 'test-customer')->assertJsonCount(1, 'riders');
        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => null]);
        $this->assertNotNull(User::where('email', 'test@example.com')->first()->email_verified_at);
        $this->assertDatabaseCount('customers', 1);
        $this->getJson('/api/admin/riders')->assertForbidden();

        $this->postJson('/api/rider/auth/logout')->assertOk();
        $this->postJson('/api/rider/auth/verify', ['email' => 'test@example.com', 'code' => $code])->assertUnprocessable();
        $code = $this->requestCode('test@example.com');
        $this->postJson('/api/rider/auth/verify', ['email' => 'test@example.com', 'code' => $code])->assertOk();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_expired_codes_and_five_failed_attempts_cannot_sign_in(): void
    {
        $code = $this->requestCode();
        $this->travel(11)->minutes();
        $this->postJson('/api/rider/auth/verify', ['email' => 'rider@example.com', 'code' => $code])->assertUnprocessable();
        $this->travelBack();

        $code = $this->requestCode();
        $wrong = $code === '111111' ? '222222' : '111111';
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/rider/auth/verify', ['email' => 'rider@example.com', 'code' => $wrong])->assertUnprocessable();
        }
        $this->postJson('/api/rider/auth/verify', ['email' => 'rider@example.com', 'code' => $code])->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_resending_a_code_invalidates_the_previous_code(): void
    {
        $old = $this->requestCode();
        $new = $this->requestCode();
        if ($old !== $new) {
            $this->postJson('/api/rider/auth/verify', ['email' => 'rider@example.com', 'code' => $old])->assertUnprocessable();
        }
        $this->postJson('/api/rider/auth/verify', ['email' => 'rider@example.com', 'code' => $new])->assertOk();
    }

    public function test_staff_cannot_use_passwordless_rider_sign_in_to_bypass_admin_passwords(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        $code = $this->requestCode($admin->email);

        $this->postJson('/api/rider/auth/verify', ['email' => $admin->email, 'code' => $code])->assertUnprocessable();
        $this->assertGuest();
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_saved_profiles_are_unique_but_siblings_and_different_birthdays_remain_separate(): void
    {
        $this->actingAs($this->riderUser());
        $input = ['first_name' => 'Emma', 'last_name' => 'Smith', 'date_of_birth' => '2010-01-01'];
        $first = $this->postJson('/api/rider/account/riders', $input)->assertCreated()->json('id');
        $same = $this->postJson('/api/rider/account/riders', [...$input, 'first_name' => '  emma  ', 'last_name' => 'SMITH'])->assertCreated()->json('id');
        $this->assertSame($first, $same);
        $this->postJson('/api/rider/account/riders', [...$input, 'first_name' => 'Hailey'])->assertCreated();
        $this->postJson('/api/rider/account/riders', [...$input, 'date_of_birth' => '2012-01-01'])->assertCreated();

        $this->getJson('/api/rider/account')->assertOk()->assertJsonCount(3, 'riders');
        $this->assertDatabaseCount('riders', 3);
    }

    private function bookingPayload(string $riderId, string $time = '09:00:00'): array
    {
        return [
            'lesson_type_id' => 'test-lesson', 'start_time' => "{$this->date}T{$time}",
            'end_time' => CarbonImmutable::parse("{$this->date} {$time}")->addHour()->format('Y-m-d\\TH:i:s'),
            'customer' => ['first_name' => 'Contact', 'last_name' => 'Name', 'email' => 'ignored@example.com'],
            'waiver' => $this->waiverPayload(),
            'riders' => [['id' => $riderId, 'first_name' => 'Saved', 'last_name' => 'Rider', 'date_of_birth' => '2010-01-01']],
        ];
    }

    public function test_multiple_bookings_reuse_a_saved_profile_and_cannot_use_another_customers_rider(): void
    {
        $user = $this->riderUser();
        $this->actingAs($user);
        $rider = $this->postJson('/api/rider/account/riders', ['first_name' => 'Saved', 'last_name' => 'Rider', 'date_of_birth' => '2010-01-01'])->assertCreated()->json('id');

        $this->postJson('/api/bookings/guest', $this->bookingPayload($rider))->assertCreated();
        $this->postJson('/api/bookings/guest', $this->bookingPayload($rider, '10:00:00'))->assertCreated();
        $this->assertDatabaseCount('riders', 1);
        $this->assertDatabaseCount('booking_riders', 2);
        $this->assertSame(1, DB::table('booking_riders')->distinct()->count('rider_id'));
        $this->assertDatabaseMissing('customers', ['email' => 'ignored@example.com']);

        $other = $this->riderUser('other@example.com');
        $this->actingAs($other)->postJson('/api/bookings/guest', $this->bookingPayload($rider, '11:00:00'))->assertNotFound();
        $this->assertDatabaseCount('booking_riders', 2);
    }

    public function test_account_reads_and_reservation_actions_are_isolated_by_owner(): void
    {
        $this->getJson('/api/rider/account')->assertUnauthorized();
        $booking = $this->guestBooking("{$this->date} 09:00:00", 'owner@example.com');
        $this->actingAs($this->riderUser('other@example.com'));
        $this->getJson('/api/rider/account')->assertOk()->assertJsonCount(0, 'riders')->assertJsonCount(0, 'reservations');
        $this->postJson("/api/rider/account/reservations/{$booking['recurring_lesson_id']}/cancel")->assertNotFound();
        $this->postJson("/api/rider/account/reservations/{$booking['recurring_lesson_id']}/skip", ['date' => $this->date])->assertNotFound();

        $this->actingAs($this->riderUser('owner@example.com'));
        $this->getJson('/api/rider/account')->assertOk()->assertJsonCount(1, 'reservations');
        $this->postJson("/api/rider/account/reservations/{$booking['recurring_lesson_id']}/cancel")->assertOk()->assertJsonPath('status', 'cancelled');
    }

    public static function providers(): array
    {
        return ['Google' => ['google'], 'Microsoft' => ['microsoft'], 'Apple' => ['apple']];
    }

    #[DataProvider('providers')]
    public function test_social_sign_in_links_only_after_mailbox_verification_then_reuses_the_same_account(string $provider): void
    {
        config(["services.{$provider}.client_id" => 'test-client', "services.{$provider}.client_secret" => 'test-secret', "services.{$provider}.redirect" => 'https://example.com/callback']);
        $profile = (new ProviderUser)->map(['id' => 'provider-user-123', 'email' => 'test@example.com', 'name' => 'Rider']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($profile);
        if ($provider === 'apple') {
            $driver->shouldReceive('stateless')->andReturnSelf();
            $driver->shouldReceive('cookieNonce')->andReturnSelf();
        }
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
        $this->get("/api/rider/oauth/{$provider}/callback")->assertRedirect('/rider/sign-in?verify_email=1');
        $this->assertDatabaseCount('social_identities', 0);
        $this->assertDatabaseCount('users', 0);

        $code = $this->requestCode('test@example.com');
        $this->postJson('/api/rider/auth/verify', ['email' => 'test@example.com', 'code' => $code])->assertOk();
        $this->assertDatabaseHas('social_identities', ['provider' => $provider, 'subject' => 'provider-user-123']);
        $this->postJson('/api/rider/auth/logout')->assertOk();
        $this->get("/api/rider/oauth/{$provider}/callback")->assertRedirect('/rider');
        $this->getJson('/api/rider/account')->assertOk();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    #[DataProvider('providers')]
    public function test_configured_social_providers_start_a_browser_bound_sign_in_flow(string $provider): void
    {
        config(["services.{$provider}.client_id" => 'test-client', "services.{$provider}.client_secret" => 'test-secret', "services.{$provider}.redirect" => "https://example.com/api/rider/oauth/{$provider}/callback"]);

        $response = $this->get("/api/rider/oauth/{$provider}/redirect")->assertRedirect();

        $location = $response->headers->get('Location');
        $this->assertStringContainsString('client_id=test-client', $location);
        $this->assertStringContainsString($provider === 'apple' ? 'nonce=' : 'state=', $location);
        if ($provider === 'apple') {
            $response->assertCookie('socialite_apple_nonce');
        }
    }

    public function test_invalid_oauth_state_and_unconfigured_providers_cannot_sign_in(): void
    {
        $this->get('/api/rider/oauth/google/redirect')->assertNotFound();
        config(['services.google.client_id' => 'test', 'services.google.client_secret' => 'secret', 'services.google.redirect' => 'https://example.com/callback']);
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->get('/api/rider/oauth/google/callback')->assertRedirect('/rider/sign-in?error=provider');
        $this->assertGuest();
        $this->assertDatabaseCount('social_identities', 0);
    }

    public function test_verified_email_claims_unassigned_admin_profiles_instead_of_creating_another_rider(): void
    {
        $existing = Rider::create(['first_name' => 'Legacy', 'last_name' => 'Rider', 'email' => 'rider@example.com', 'date_of_birth' => '2010-01-01']);
        $user = $this->riderUser();
        $this->actingAs($user);
        $this->getJson('/api/rider/account')->assertOk()->assertJsonPath('riders.0.id', $existing->id);
        $this->postJson('/api/rider/account/riders', ['first_name' => ' legacy ', 'last_name' => 'RIDER', 'date_of_birth' => '2010-01-01'])
            ->assertCreated()->assertJsonPath('id', $existing->id);
        $this->assertDatabaseCount('riders', 1);
    }

    public function test_guest_cannot_add_riders_to_an_account_that_has_already_been_claimed(): void
    {
        $user = $this->riderUser();
        $payload = $this->bookingPayload('not-used');
        unset($payload['riders'][0]['id']);
        $payload['customer']['email'] = $user->email;

        $this->postJson('/api/bookings/guest', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('customer.email');
        $this->assertDatabaseCount('riders', 0);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_admin_cannot_create_an_exact_duplicate_rider(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $input = ['first_name' => 'Same', 'last_name' => 'Rider', 'customer_id' => 'test-customer', 'date_of_birth' => '2010-01-01'];
        $this->postJson('/api/admin/riders', $input)->assertCreated();
        $this->postJson('/api/admin/riders', [...$input, 'first_name' => ' same '])->assertCreated();
        $this->assertDatabaseCount('riders', 1);
    }
}
