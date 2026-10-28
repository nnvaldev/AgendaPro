<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Notifications\BookingCancelledByBusiness;
use App\Notifications\BookingCancelledByCustomer;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingReminder;
use App\Notifications\NewBookingReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesBookableBusiness;
use Tests\TestCase;

class BookingNotificationsTest extends TestCase
{
    use CreatesBookableBusiness, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToMondayMorning();
        Notification::fake();
    }

    private function bookingFor(array $attributes = []): Booking
    {
        [, $service, $staff] = $this->bookableBusiness();

        return Booking::factory()->create([
            'service_id' => $service->id,
            'staff_member_id' => $staff[0]->id,
            'customer_email' => 'ana@example.com',
            'starts_at' => $this->laPaz('2026-11-03 09:00')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 09:30')->utc(),
            ...$attributes,
        ]);
    }

    public function test_booking_notifies_the_customer_and_the_owner()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->postJson(route('api.businesses.bookings.store', $business->slug), [
            'service_id' => $service->id,
            'starts_at' => '2026-11-03T09:00:00-04:00',
            'customer_name' => 'Ana Choque',
            'customer_email' => 'ana@example.com',
        ])->assertCreated();

        Notification::assertSentOnDemand(
            BookingConfirmed::class,
            fn ($notification, $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === ['ana@example.com' => 'Ana Choque'],
        );
        Notification::assertSentTo($business->owner, NewBookingReceived::class);
    }

    public function test_confirmation_email_has_the_manage_link_and_a_calendar_event()
    {
        $booking = $this->bookingFor();

        $mail = (new BookingConfirmed($booking))->toMail(new AnonymousNotifiable);

        $this->assertSame("Reserva confirmada en {$booking->business->name}", $mail->subject);
        $this->assertSame(route('public.booking.manage', $booking->manage_token), $mail->actionUrl);
        $this->assertStringContainsString('martes 3 de noviembre, 09:00', implode("\n", $mail->introLines));

        $ics = $mail->rawAttachments[0]['data'];
        $this->assertStringContainsString('DTSTART:20261103T130000Z', $ics);
        $this->assertStringContainsString('DTEND:20261103T133000Z', $ics);
    }

    public function test_customer_cancellation_notifies_the_owner()
    {
        $booking = $this->bookingFor();

        $this->postJson(route('api.bookings.cancel', $booking->manage_token))->assertOk();

        Notification::assertSentTo($booking->business->owner, BookingCancelledByCustomer::class);
        Notification::assertCount(1);
    }

    public function test_business_cancellation_notifies_the_customer_but_other_statuses_do_not()
    {
        $booking = $this->bookingFor();
        $this->actingAs($booking->business->owner);

        $this->patch(route('agenda.status.update', $booking), ['status' => 'completed']);
        Notification::assertNothingSent();

        $other = $this->bookingFor();
        $this->actingAs($other->business->owner);
        $this->patch(route('agenda.status.update', $other), ['status' => 'cancelled']);

        Notification::assertSentOnDemand(BookingCancelledByBusiness::class);
    }

    public function test_reminders_go_to_bookings_made_at_least_a_day_ahead_only_once()
    {
        // Reservada el viernes para el martes: recibe recordatorio.
        $this->travelTo($this->laPaz('2026-10-30 10:00'));
        $early = $this->bookingFor();

        // Reservada el lunes a las 12:00 para el martes 09:00: no.
        $this->travelTo($this->laPaz('2026-11-02 12:00'));
        $late = $this->bookingFor();

        // Lunes 12:30: las dos reservas del martes 09:00 están a menos de 24 h.
        $this->travelTo($this->laPaz('2026-11-02 12:30'));
        $this->artisan('bookings:send-reminders')->assertSuccessful();
        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertSentOnDemandTimes(BookingReminder::class, 1);
        $this->assertNotNull($early->fresh()->reminder_sent_at);
        $this->assertNull($late->fresh()->reminder_sent_at);
    }

    public function test_cancelled_bookings_get_no_reminder()
    {
        $this->travelTo($this->laPaz('2026-10-30 10:00'));
        $this->bookingFor(['status' => 'cancelled']);

        $this->travelTo($this->laPaz('2026-11-02 12:30'));
        $this->artisan('bookings:send-reminders');

        Notification::assertNothingSent();
    }
}
