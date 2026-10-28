<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBookingPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_page_shows_only_active_services()
    {
        $business = Business::factory()->create();
        Service::factory()->for($business)->create(['name' => 'Visible']);
        Service::factory()->for($business)->inactive()->create();

        $this->get(route('public.booking', $business->slug))->assertInertia(fn (Assert $page) => $page
            ->component('public/Book')
            ->where('business.slug', $business->slug)
            ->has('business.services', 1)
            ->where('business.services.0.name', 'Visible'));
    }

    public function test_unknown_business_returns_not_found()
    {
        $this->get('/reservar/no-existe')->assertNotFound();
    }

    public function test_manage_page_does_not_expose_contact_data()
    {
        $booking = Booking::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30)]);

        $this->get(route('public.booking.manage', $booking->manage_token))->assertInertia(fn (Assert $page) => $page
            ->component('public/ManageBooking')
            ->where('cancellable', true)
            ->missing('booking.customer_email')
            ->missing('booking.manage_token'));
    }
}
