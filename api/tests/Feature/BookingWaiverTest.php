<?php

namespace Tests\Feature;

use App\Models\Rider;
use App\Models\User;
use App\Models\Waiver;
use App\Services\BookingService;
use App\Services\RiderAccountService;
use App\Services\WaiverService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsSchedule;
use Tests\TestCase;

class BookingWaiverTest extends TestCase
{
    use BuildsSchedule, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-14 12:00:00', 'America/Denver'));
        $this->buildSchedule();
    }

    private function payload(): array
    {
        return [
            'lesson_type_id' => 'test-lesson', 'start_time' => "{$this->date}T09:00:00", 'end_time' => "{$this->date}T10:00:00",
            'customer' => ['first_name' => 'Signing', 'last_name' => 'Parent', 'email' => 'parent@example.com'],
            'riders' => [['first_name' => 'Minor', 'last_name' => 'Rider', 'date_of_birth' => '2015-01-01']],
            'waiver' => $this->waiverPayload(),
        ];
    }

    public static function invalidBirthdays(): array
    {
        return [[''], [null], ['2099-01-01'], ['2026-02-30'], ['0026-01-01']];
    }

    #[DataProvider('invalidBirthdays')]
    public function test_booking_rejects_missing_future_and_invalid_birthdays(?string $birthday): void
    {
        $data = $this->payload();
        $data['riders'][0]['date_of_birth'] = $birthday;
        $this->postRiderBooking($data)->assertUnprocessable()->assertJsonValidationErrors('riders.0.date_of_birth');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('riders', 0);
        $this->assertDatabaseCount('customers', 2);
    }

    public function test_guest_booking_reuses_a_matching_admin_entered_rider(): void
    {
        $rider = Rider::create(['first_name' => 'Minor', 'last_name' => 'Rider', 'email' => 'parent@example.com', 'date_of_birth' => '2015-01-01']);
        $this->postRiderBooking($this->payload())->assertCreated();
        $this->assertDatabaseCount('riders', 1);
        $this->assertDatabaseHas('booking_riders', ['rider_id' => $rider->id]);
        $this->assertNotNull($rider->fresh()->customer_id);
    }

    public function test_guest_repeat_booking_reuses_a_rider_and_conflicting_birthday_does_not_create_a_duplicate(): void
    {
        $data = $this->payload();
        $this->postRiderBooking($data)->assertCreated();
        $data['start_time'] = "{$this->date}T10:00:00";
        $data['end_time'] = "{$this->date}T11:00:00";
        $data['customer']['email'] = 'PARENT@example.com';
        $data['riders'][0]['first_name'] = '  minor  ';
        $this->postRiderBooking($data)->assertCreated();
        $this->assertDatabaseCount('riders', 1);
        $data['start_time'] = "{$this->date}T11:00:00";
        $data['end_time'] = "{$this->date}T12:00:00";
        $data['riders'][0]['date_of_birth'] = '2014-01-01';
        $this->postRiderBooking($data)->assertUnprocessable()->assertJsonValidationErrors('riders');
        $this->assertDatabaseCount('riders', 1);
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_saved_rider_missing_birthday_can_complete_it_without_another_profile(): void
    {
        $accounts = app(RiderAccountService::class);
        $user = $accounts->signIn('parent@example.com');
        $customer = $accounts->customerFor($user);
        $rider = Rider::create(['customer_id' => $customer->id, 'first_name' => 'Minor', 'last_name' => 'Rider']);
        $this->actingAs($user);
        $data = $this->payload();
        $data['riders'][0]['id'] = $rider->id;
        $this->postRiderBooking($data)->assertCreated();
        $this->assertDatabaseCount('riders', 1);
        $this->assertSame('2015-01-01', $rider->fresh()->date_of_birth->toDateString());
    }

    public function test_guest_booking_cannot_claim_a_standalone_rider_linked_to_another_login(): void
    {
        $owner = User::factory()->create();
        $rider = Rider::create(['user_id' => $owner->id, 'first_name' => 'Minor', 'last_name' => 'Rider', 'email' => 'parent@example.com', 'date_of_birth' => '2015-01-01']);
        $bookingId = $this->postRiderBooking($this->payload())->assertCreated()->json('booking_id');
        $this->assertNull($rider->fresh()->customer_id);
        $this->assertDatabaseMissing('booking_riders', ['booking_id' => $bookingId, 'rider_id' => $rider->id]);
    }

    public function test_public_document_is_the_current_effective_waiver(): void
    {
        Waiver::create(['id' => 'future', 'title' => 'Future', 'version' => 2, 'content' => 'Not yet effective.', 'effective_date' => '2099-01-01', 'active' => true]);

        $this->getJson('/api/waiver')->assertOk()->assertJsonPath('id', 'test-waiver')
            ->assertJsonPath('document_hash', WaiverService::fingerprint(Waiver::findOrFail('test-waiver')))
            ->assertJsonPath('current_date', '2026-10-14');
    }

    public function test_successful_booking_saves_the_exact_signed_document_for_each_rider(): void
    {
        DB::table('lesson_types')->update(['required_rider_count' => 2, 'required_horse_count' => 2]);
        $this->setActive('horses', ['test-h2'], true);
        $input = $this->payload();
        $input['riders'][] = ['first_name' => 'Second', 'last_name' => 'Rider', 'date_of_birth' => '2013-01-01'];
        $input['waiver'] = $this->waiverPayload(2);

        $response = $this->withHeader('User-Agent', 'Waiver test browser')->postRiderBooking($input)->assertCreated();

        $bookingId = $response->json('booking_id');
        $records = DB::table('waiver_acceptances')->where('booking_id', $bookingId)->get();
        $this->assertCount(2, $records);
        $this->assertSame(2, $records->unique('rider_id')->count());
        foreach ($records as $record) {
            $this->assertSame('Test Parent', $record->signer_name);
            $this->assertSame('parent@example.com', $record->signer_email);
            $this->assertSame('parent', $record->relationship_to_rider);
            $this->assertSame('Existing riding waiver test text.', $record->waiver_content);
            $this->assertSame(1, $record->waiver_version);
            $this->assertSame('Waiver test browser', $record->user_agent);
            $this->assertNotNull($record->ip_address);
            $this->assertNotNull($record->accepted_at);
        }
        Waiver::findOrFail('test-waiver')->update(['content' => 'Changed text after signing.']);
        $this->assertSame('Existing riding waiver test text.', DB::table('waiver_acceptances')->first()->waiver_content);
    }

    public static function invalidSignatures(): array
    {
        return [
            'missing signature' => ['signer_name', ''],
            'missing relationship' => ['relationship_to_rider', ''],
            'not agreed' => ['accepted', false],
            'not authorized' => ['authorized', false],
            'minor signing for themselves' => ['relationship_to_rider', 'self'],
        ];
    }

    #[DataProvider('invalidSignatures')]
    public function test_unsigned_or_unauthorized_bookings_leave_no_partial_reservation(string $field, mixed $value): void
    {
        $input = $this->payload();
        $input['waiver']['signatures'][0][$field] = $value;

        $this->postRiderBooking($input)->assertUnprocessable();

        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('recurring_lessons', 0);
        $this->assertDatabaseCount('riders', 0);
        $this->assertDatabaseCount('waiver_acceptances', 0);
    }

    public function test_a_waiver_is_required_even_if_the_client_bypasses_the_form(): void
    {
        $input = $this->payload();
        unset($input['waiver']);

        $this->postRiderBooking($input)->assertUnprocessable()->assertJsonValidationErrors('waiver.id');
        try {
            app(BookingService::class)->createGuestBooking($input);
            $this->fail('The domain service must enforce the waiver too.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('bookings', 0);
            $this->assertDatabaseCount('waiver_acceptances', 0);
        }
    }

    public function test_changed_waivers_must_be_read_and_signed_again(): void
    {
        $input = $this->payload();
        Waiver::findOrFail('test-waiver')->update(['content' => 'Updated waiver text.']);

        $this->postRiderBooking($input)->assertUnprocessable()->assertJsonValidationErrors('waiver');
        $this->assertDatabaseCount('bookings', 0);
        $input['waiver'] = $this->waiverPayload();
        $this->postRiderBooking($input)->assertCreated();
    }

    public function test_a_signature_is_required_for_every_rider(): void
    {
        $input = $this->payload();
        DB::table('lesson_types')->update(['required_rider_count' => 2, 'required_horse_count' => 2]);
        $this->setActive('horses', ['test-h2'], true);
        $input['riders'][] = ['first_name' => 'Second', 'last_name' => 'Rider', 'date_of_birth' => '2013-01-01'];

        $this->postRiderBooking($input)->assertUnprocessable()->assertJsonValidationErrors('waiver.signatures');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('riders', 0);
        $this->assertDatabaseCount('waiver_acceptances', 0);
    }

    public function test_an_adult_can_sign_for_themselves(): void
    {
        $input = $this->payload();
        $input['riders'][0]['date_of_birth'] = '1990-01-01';
        $input['waiver']['signatures'][0]['relationship_to_rider'] = 'self';

        $this->postRiderBooking($input)->assertCreated();
        $this->assertDatabaseHas('waiver_acceptances', ['relationship_to_rider' => 'self']);
    }

    public function test_no_current_waiver_blocks_booking(): void
    {
        $input = $this->payload();
        DB::table('waivers')->update(['active' => false]);

        $this->getJson('/api/waiver')->assertNotFound();
        $this->postRiderBooking($input)->assertUnprocessable();
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_signed_waivers_are_visible_only_to_admins_and_remain_after_cancellation(): void
    {
        $booking = $this->postRiderBooking($this->payload())->assertCreated()->json();
        $path = "/api/admin/bookings/{$booking['booking_id']}/waivers";

        $this->getJson($path)->assertForbidden();
        $this->actingAs(User::factory()->create())->getJson($path)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson("/api/admin/bookings/{$booking['booking_id']}/cancel")->assertOk();
        $this->getJson($path)->assertOk()->assertJsonCount(1)->assertJsonPath('0.waiver_content', 'Existing riding waiver test text.');
        $this->getJson("/api/admin/recurring-lessons/{$booking['recurring_lesson_id']}/waivers")->assertOk()->assertJsonCount(1);
        $this->getJson('/api/admin/bookings')->assertOk()->assertJsonPath('0.waiver_acceptances_count', 1);
    }
}
