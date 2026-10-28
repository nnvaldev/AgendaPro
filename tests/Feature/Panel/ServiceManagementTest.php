<?php

namespace Tests\Feature\Panel;

use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ActsAsBusinessOwner;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use ActsAsBusinessOwner, RefreshDatabase;

    public function test_owner_can_create_a_service_with_its_staff()
    {
        $business = $this->actingAsOwner();
        $member = StaffMember::factory()->for($business)->create();

        $this->post(route('services.store'), [
            'name' => 'Corte + barba',
            'duration_minutes' => 45,
            'price' => '79.90',
            'is_active' => true,
            'staff_member_ids' => [$member->id],
        ])->assertRedirect(route('services.index'));

        $service = Service::sole();
        $this->assertSame(7990, $service->price_cents);
        $this->assertSame(45, $service->duration_minutes);
        $this->assertSame([$member->id], $service->staffMembers()->pluck('staff_members.id')->all());
    }

    public function test_edit_form_shows_the_price_in_bolivianos()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create(['price_cents' => 5050]);

        $this->get(route('services.edit', $service))->assertInertia(fn (Assert $page) => $page
            ->component('services/Form')
            ->where('service.price', '50.50'));
    }

    public function test_service_data_is_validated()
    {
        $this->actingAsOwner();

        $this->post(route('services.store'), [
            'name' => '',
            'duration_minutes' => 2,
            'price' => '-5',
        ])->assertSessionHasErrors(['name', 'duration_minutes', 'price']);
    }

    public function test_services_with_bookings_are_not_deleted()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();
        Booking::factory()->create(['service_id' => $service->id]);

        $this->delete(route('services.destroy', $service));

        $this->assertModelExists($service);
    }

    public function test_services_without_bookings_can_be_deleted()
    {
        $business = $this->actingAsOwner();
        $service = Service::factory()->for($business)->create();

        $this->delete(route('services.destroy', $service))->assertRedirect(route('services.index'));

        $this->assertModelMissing($service);
    }
}
