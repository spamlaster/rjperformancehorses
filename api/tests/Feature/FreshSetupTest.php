<?php

namespace Tests\Feature;

use App\Models\LessonType;
use App\Models\User;
use App\Models\Waiver;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DefaultLessonTypesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreshSetupTest extends TestCase
{
    use RefreshDatabase;

    private function lesson(array $changes = []): array
    {
        return array_replace(['name' => 'Private lesson', 'duration_minutes' => 45, 'price' => 55,
            'required_rider_count' => 1, 'required_horse_count' => 1, 'active' => true], $changes);
    }

    public function test_default_install_includes_six_active_lessons_without_demo_records(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (['users', 'customers', 'riders', 'horses', 'waivers', 'bookings', 'instructor_availability', 'horse_recurring_availability'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('lesson_types', 6);
        $this->getJson('/api/lesson-types')->assertOk()->assertJsonCount(6);
        $this->assertDatabaseHas('lesson_types', ['name' => 'Group Lesson (60 min)', 'price' => 100, 'duration_minutes' => 60, 'required_rider_count' => 2, 'required_horse_count' => 2]);
        $this->assertDatabaseHas('business_config', ['config_key' => 'business_timezone', 'config_value' => 'America/Denver']);
    }

    public function test_reloading_defaults_preserves_edits_and_does_not_duplicate_lessons(): void
    {
        $this->seed(DefaultLessonTypesSeeder::class);
        LessonType::query()->findOrFail('default-private-30')->update(['name' => 'Custom private', 'price' => 80, 'active' => false]);
        $this->seed(DefaultLessonTypesSeeder::class);
        $this->assertDatabaseCount('lesson_types', 6);
        $this->assertDatabaseHas('lesson_types', ['id' => 'default-private-30', 'name' => 'Custom private', 'price' => 80, 'active' => false]);
    }

    public function test_loading_defaults_keeps_an_existing_matching_lesson(): void
    {
        LessonType::create($this->lesson(['name' => '30 Minute Private', 'price' => 70]));
        $this->seed(DefaultLessonTypesSeeder::class);
        $this->assertDatabaseCount('lesson_types', 6);
        $this->assertDatabaseHas('lesson_types', ['name' => '30 Minute Private', 'price' => 70]);
    }

    public function test_setup_and_creation_require_admin_access(): void
    {
        $this->getJson('/api/admin/setup')->assertUnauthorized();
        $this->postJson('/api/admin/lesson-types', $this->lesson())->assertUnauthorized();
        $this->postJson('/api/admin/waivers', ['title' => 'Title', 'content' => 'Text'])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/admin/setup')->assertForbidden();
        $this->postJson('/api/admin/lesson-types', $this->lesson())->assertForbidden();
        $this->postJson('/api/admin/waivers', ['title' => 'Title', 'content' => 'Text'])->assertForbidden();
        $this->assertDatabaseCount('lesson_types', 0);
        $this->assertDatabaseCount('waivers', 0);
    }

    public function test_empty_install_returns_setup_state_without_adding_records(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->getJson('/api/admin/setup')->assertOk()->assertJsonPath('lesson_count', 0)
            ->assertJsonPath('horse_count', 0)->assertJsonPath('instructor_hours', false)
            ->assertJsonPath('horse_hours', false)->assertJsonPath('waiver', null)
            ->assertJsonPath('business_timezone', 'America/Denver');
        $this->assertDatabaseCount('waivers', 0);
        $this->assertDatabaseCount('lesson_types', 0);
    }

    public function test_admin_can_create_edit_and_deactivate_a_lesson_type(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $id = $this->postJson('/api/admin/lesson-types', $this->lesson())->assertCreated()->json('id');
        $this->assertDatabaseHas('lesson_types', ['id' => $id, 'name' => 'Private lesson', 'required_horse_count' => 1]);
        $this->getJson('/api/lesson-types')->assertOk()->assertJsonPath('0.id', $id);
        $this->patchJson("/api/admin/lesson-types/{$id}", ['name' => 'Joined lesson', 'price' => 100,
            'required_rider_count' => 2, 'required_horse_count' => 2, 'active' => false])->assertOk()->assertJsonPath('active', false);
        $this->assertDatabaseHas('lesson_types', ['id' => $id, 'required_rider_count' => 2, 'required_horse_count' => 2, 'active' => false]);
        $this->getJson('/api/lesson-types')->assertOk()->assertExactJson([]);
        $this->getJson('/api/admin/lesson-types')->assertOk()->assertJsonCount(1);
    }

    public function test_invalid_lesson_configuration_does_not_create_a_record(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson('/api/admin/lesson-types', $this->lesson(['name' => '', 'duration_minutes' => 0,
            'price' => -1, 'required_rider_count' => 0, 'required_horse_count' => 0]))
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'duration_minutes', 'price', 'required_rider_count', 'required_horse_count']);
        $this->assertDatabaseCount('lesson_types', 0);
    }

    public function test_waiver_publication_versions_the_document_without_overwriting_old_text(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $firstId = $this->postJson('/api/admin/waivers', ['title' => 'Original', 'content' => 'Original waiver text.'])
            ->assertCreated()->assertJsonPath('version', 1)->json('id');
        $this->postJson('/api/admin/waivers', ['title' => 'Updated', 'content' => 'Updated waiver text.'])
            ->assertCreated()->assertJsonPath('version', 2);
        $this->assertDatabaseHas('waivers', ['id' => $firstId, 'content' => 'Original waiver text.', 'active' => false]);
        $this->getJson('/api/waiver')->assertOk()->assertJsonPath('content', 'Updated waiver text.');
        $this->postJson('/api/admin/waivers', ['title' => '', 'content' => ''])->assertUnprocessable()->assertJsonValidationErrors(['title', 'content']);
        $this->assertDatabaseCount('waivers', 2);
        $this->assertSame(1, Waiver::query()->where('active', true)->count());
    }

    public function test_reset_command_refuses_non_local_environment(): void
    {
        $admin = User::factory()->admin()->create();
        $this->artisan('app:fresh', ['--email' => $admin->email, '--force' => true])->assertFailed();
        $this->assertModelExists($admin);
    }
}
