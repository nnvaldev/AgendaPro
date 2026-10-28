<?php

namespace Tests\Feature\Panel;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ActsAsBusinessOwner;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use ActsAsBusinessOwner, RefreshDatabase;

    private function bookingAt(Service $service, StaffMember $member, string $laPazTime, array $attributes = []): Booking
    {
        $start = CarbonImmutable::parse($laPazTime, 'America/La_Paz')->utc();

        return Booking::factory()->create([
            'service_id' => $service->id,
            'staff_member_id' => $member->id,
            'starts_at' => $start,
            'ends_at' => $start->addMinutes(30),
            ...$attributes,
        ]);
    }

    public function test_agenda_lists_the_bookings_of_the_requested_local_day()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();
        $member = StaffMember::factory()->for($business)->create();

        // 20:30 en La Paz ya es el día siguiente en UTC: debe contar como el 3.
        $late = $this->bookingAt($service, $member, '2026-11-03 20:30');
        $this->bookingAt($service, $member, '2026-11-03 09:00');
        $this->bookingAt($service, $member, '2026-11-04 09:00');

        $this->get(route('agenda.index', ['date' => '2026-11-03']))->assertInertia(fn (Assert $page) => $page
            ->component('agenda/Index')
            ->where('date', '2026-11-03')
            ->has('bookings', 2)
            ->where('bookings.1.id', $late->id)
            ->where('bookings.1.starts_at', '2026-11-03T20:30:00-04:00'));
    }

    public function test_owner_can_mark_a_booking_as_completed_or_no_show()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();
        $member = StaffMember::factory()->for($business)->create();
        $booking = $this->bookingAt($service, $member, '2026-11-03 09:00');

        $this->patch(route('agenda.status.update', $booking), ['status' => 'completed']);
        $this->assertSame(BookingStatus::Completed, $booking->fresh()->status);

        $this->patch(route('agenda.status.update', $booking), ['status' => 'no_show']);
        $this->assertSame(BookingStatus::NoShow, $booking->fresh()->status);
    }

    public function test_cancelled_bookings_cannot_be_reactivated()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();
        $member = StaffMember::factory()->for($business)->create();
        $booking = $this->bookingAt($service, $member, '2026-11-03 09:00', ['status' => BookingStatus::Cancelled]);

        $this->patch(route('agenda.status.update', $booking), ['status' => 'confirmed']);

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
    }

    public function test_cancelling_from_the_panel_records_the_time()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();
        $member = StaffMember::factory()->for($business)->create();
        $booking = $this->bookingAt($service, $member, '2026-11-03 09:00');

        $this->patch(route('agenda.status.update', $booking), ['status' => 'cancelled']);

        $this->assertNotNull($booking->fresh()->cancelled_at);
    }
}
