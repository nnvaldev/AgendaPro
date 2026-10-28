<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBookableBusiness;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use CreatesBookableBusiness, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToMondayMorning();
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(array $overrides = []): array
    {
        return [
            'starts_at' => '2026-11-03T09:00:00-04:00',
            'customer_name' => 'Ana Choque',
            'customer_email' => 'ana@example.com',
            'customer_phone' => '+591 71234567',
            ...$overrides,
        ];
    }

    public function test_guest_can_book_an_available_slot()
    {
        [$business, $service, $staff] = $this->bookableBusiness();

        $response = $this->postJson(
            route('api.businesses.bookings.store', $business->slug),
            $this->bookingPayload(['service_id' => $service->id, 'staff_member_id' => $staff[0]->id]),
        );

        $response->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.starts_at', '2026-11-03T09:00:00-04:00')
            ->assertJsonPath('data.ends_at', '2026-11-03T09:30:00-04:00')
            ->assertJsonPath('data.price_cents', 5000);

        $booking = Booking::sole();
        $this->assertTrue($booking->starts_at->equalTo($this->laPaz('2026-11-03 09:00')));
        $this->assertSame($business->id, $booking->business_id);
        $this->assertSame($booking->manage_token, $response->json('data.manage_token'));
    }

    public function test_price_is_kept_when_the_service_price_changes()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->postJson(route('api.businesses.bookings.store', $business->slug), $this->bookingPayload(['service_id' => $service->id]))
            ->assertCreated();

        $service->update(['price_cents' => 9900]);

        $this->assertSame(5000, Booking::sole()->price_cents);
    }

    public function test_taken_slot_cannot_be_booked_again()
    {
        [$business, $service, $staff] = $this->bookableBusiness();
        $payload = $this->bookingPayload(['service_id' => $service->id, 'staff_member_id' => $staff[0]->id]);

        $this->postJson(route('api.businesses.bookings.store', $business->slug), $payload)->assertCreated();

        $this->postJson(route('api.businesses.bookings.store', $business->slug), [...$payload, 'starts_at' => '2026-11-03T09:15:00-04:00'])
            ->assertConflict()
            ->assertJsonPath('message', 'El horario seleccionado ya no está disponible.');

        $this->assertSame(1, Booking::count());
    }

    public function test_without_staff_the_first_free_professional_is_assigned()
    {
        [$business, $service, $staff] = $this->bookableBusiness(staffCount: 2);
        $url = route('api.businesses.bookings.store', $business->slug);
        $payload = $this->bookingPayload(['service_id' => $service->id]);

        $this->postJson($url, $payload)->assertCreated();
        $this->postJson($url, $payload)->assertCreated();
        $this->postJson($url, $payload)->assertConflict();

        $this->assertEqualsCanonicalizing(
            $staff->pluck('id')->all(),
            Booking::pluck('staff_member_id')->all(),
        );
    }

    public function test_slots_outside_working_hours_are_rejected()
    {
        [$business, $service] = $this->bookableBusiness();

        // 10:45 + 30 min termina después del cierre de las 11:00.
        $this->postJson(route('api.businesses.bookings.store', $business->slug), $this->bookingPayload([
            'service_id' => $service->id,
            'starts_at' => '2026-11-03T10:45:00-04:00',
        ]))->assertConflict();
    }

    public function test_customer_data_is_validated()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->postJson(route('api.businesses.bookings.store', $business->slug), [
            'service_id' => $service->id,
            'starts_at' => 'mañana',
            'customer_email' => 'no-es-un-email',
        ])->assertUnprocessable()->assertJsonValidationErrors(['starts_at', 'customer_name', 'customer_email']);
    }

    public function test_database_rejects_overlapping_bookings_for_the_same_staff_member()
    {
        [, $service, $staff] = $this->bookableBusiness();
        $attributes = ['service_id' => $service->id, 'staff_member_id' => $staff[0]->id];

        Booking::factory()->create([
            ...$attributes,
            'starts_at' => $this->laPaz('2026-11-03 09:00')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 09:30')->utc(),
        ]);

        $this->expectException(QueryException::class);

        // Simula una reserva que se saltó la validación (p. ej. dos peticiones simultáneas).
        Booking::factory()->create([
            ...$attributes,
            'starts_at' => $this->laPaz('2026-11-03 09:15')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 09:45')->utc(),
        ]);
    }

    public function test_booking_can_be_viewed_and_cancelled_with_its_token()
    {
        [$business, $service, $staff] = $this->bookableBusiness();
        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'staff_member_id' => $staff[0]->id,
            'starts_at' => $this->laPaz('2026-11-03 09:00')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 09:30')->utc(),
        ]);

        $this->getJson(route('api.bookings.show', $booking->manage_token))
            ->assertOk()
            ->assertJsonPath('data.service', $service->name)
            ->assertJsonMissingPath('data.manage_token');

        $this->postJson(route('api.bookings.cancel', $booking->manage_token))
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);

        // El horario vuelve a estar libre.
        $this->postJson(route('api.businesses.bookings.store', $business->slug), $this->bookingPayload(['service_id' => $service->id]))
            ->assertCreated();
    }

    public function test_cancelled_booking_cannot_be_cancelled_again()
    {
        [, $service] = $this->bookableBusiness();
        $booking = Booking::factory()->cancelled()->create(['service_id' => $service->id]);

        $this->postJson(route('api.bookings.cancel', $booking->manage_token))->assertConflict();
    }

    public function test_unknown_or_malformed_tokens_return_not_found()
    {
        $this->getJson('/api/bookings/no-es-un-uuid')->assertNotFound();
        $this->getJson('/api/bookings/'.fake()->uuid())->assertNotFound();
    }
}
