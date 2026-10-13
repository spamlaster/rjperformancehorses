<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

/** Authorization (replaces the RLS cases in tests/availability.sql) and admin CRUD. */
class AdminApiTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    private const ADMIN_READS = ['instructor-availability', 'horse-availability', 'blocked-times', 'bookings', 'customers', 'riders', 'horses', 'lesson-types'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildSchedule();
    }

    public function test_guests_and_non_admins_are_refused(): void
    {
        foreach (self::ADMIN_READS as $path) {
            $this->getJson("/api/admin/{$path}")->assertUnauthorized(); // 39–44
        }

        $this->actingAs(User::factory()->create());
        foreach (self::ADMIN_READS as $path) {
            $this->getJson("/api/admin/{$path}")->assertForbidden();
        }
        $this->patchJson('/api/admin/config', ['business_timezone' => 'UTC'])->assertForbidden(); // 52
        $this->assertSame('America/Denver', DB::table('business_config')->value('config_value'));
        $this->assertNull(User::query()->where('role', '!=', null)->first(), '51/53 no route can create or promote admins');
    }

    public function test_login_only_admits_active_admins(): void
    {
        $this->withHeader('Referer', 'http://localhost:5173/admin/login'); // SPA requests are stateful
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        User::factory()->create(['email' => 'rider@example.com']);

        $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/auth/login', ['email' => 'rider@example.com', 'password' => 'password'])->assertForbidden();
        $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertOk()->assertJsonPath('is_admin', true)->assertJsonPath('admin_profile.role', 'admin');

        $admin->update(['is_active' => false]);
        $this->app['auth']->forgetGuards(); // the next request reloads the user, as a real one would
        $this->getJson('/api/admin/horses')->assertForbidden();
    }

    public function test_admin_crud_matches_the_old_service_layer(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $horse = $this->postJson('/api/admin/horses', ['name' => 'Verify horse', 'experience_level' => 'intermediate'])
            ->assertCreated()->assertJsonPath('active', true)->json();
        $this->patchJson("/api/admin/horses/{$horse['id']}", ['breed' => 'Test breed'])->assertOk()->assertJsonPath('name', 'Verify horse');
        $this->postJson('/api/admin/horses', ['name' => 'Verify horse'])->assertStatus(409);
        $this->getJson('/api/admin/horses?include_inactive=0')->assertOk()->assertJsonMissing(['id' => 'test-h2']);
        $this->deleteJson("/api/admin/horses/{$horse['id']}")->assertOk();

        $this->insertBooking('test-booking', "{$this->date} 09:00", "{$this->date} 10:00");
        $this->deleteJson('/api/admin/horses/test-h1')->assertStatus(409); // still booked

        $window = $this->postJson('/api/admin/instructor-availability', ['day_of_week' => 0, 'start_time' => '05:00', 'end_time' => '06:00'])
            ->assertCreated()->assertJsonPath('start_time', '05:00:00')->json();
        $this->patchJson("/api/admin/instructor-availability/{$window['id']}", ['active' => false])->assertOk()->assertJsonPath('active', false);
        $this->patchJson('/api/admin/instructor-availability/missing', ['active' => false])->assertNotFound();

        $this->patchJson('/api/admin/horse-availability/test-a-test-h1', ['active' => false])->assertOk();
        $this->postJson('/api/admin/blocked-times', ['date' => $this->date, 'start_time' => '09:00', 'end_time' => '10:00', 'reason' => 'Farrier'])
            ->assertCreated()->assertJsonPath('horse_id', null);

        $rider = $this->postJson('/api/admin/riders', ['first_name' => 'Verify', 'last_name' => 'Rider', 'riding_experience' => 'beginner'])->assertCreated()->json();
        $this->patchJson("/api/admin/riders/{$rider['id']}", ['phone' => '555-0100'])->assertOk()->assertJsonPath('first_name', 'Verify');
        $this->deleteJson("/api/admin/riders/{$rider['id']}")->assertOk();

        $this->patchJson('/api/admin/lesson-types/test-lesson', ['price' => 99.5])->assertOk()->assertJsonPath('price', 99.5);
        $this->patchJson('/api/admin/config', ['business_timezone' => 'invalid/timezone'])->assertUnprocessable(); // 55
        $this->getJson('/api/admin/bookings')->assertOk()->assertJsonPath('0.start_time', "{$this->date}T09:00:00")->assertJsonPath('0.rider_names', []);
    }

    public function test_public_reads(): void
    {
        DB::table('lesson_types')->insert(['id' => 'inactive', 'name' => 'Old', 'duration_minutes' => 30, 'price' => 1, 'active' => false]);
        $this->getJson('/api/lesson-types')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', 'test-lesson');
        $this->getJson('/api/config')->assertOk()->assertExactJson(['business_timezone' => 'America/Denver']);
    }
}
