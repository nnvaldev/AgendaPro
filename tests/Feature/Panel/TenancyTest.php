<?php

namespace Tests\Feature\Panel;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\ActsAsBusinessOwner;
use Tests\TestCase;

/**
 * Un dueño nunca debe ver ni tocar datos de otro negocio, aunque adivine IDs.
 */
class TenancyTest extends TestCase
{
    use ActsAsBusinessOwner, RefreshDatabase;

    private Business $otherBusiness;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otherBusiness = Business::factory()->create();
    }

    public function test_listings_only_include_the_owners_records()
    {
        $business = $this->actingAsOwner();
        Service::factory()->for($business)->create(['name' => 'Mío']);
        Service::factory()->for($this->otherBusiness)->create(['name' => 'Ajeno']);

        $this->get(route('services.index'))->assertInertia(fn (Assert $page) => $page
            ->component('services/Index')
            ->has('services', 1)
            ->where('services.0.name', 'Mío'));
    }

    public function test_records_of_another_business_return_not_found()
    {
        $this->actingAsOwner();
        $service = Service::factory()->for($this->otherBusiness)->create();
        $member = StaffMember::factory()->for($this->otherBusiness)->create();
        $booking = Booking::factory()->create(['service_id' => $service->id, 'staff_member_id' => $member->id]);

        $this->get(route('services.edit', $service))->assertNotFound();
        $this->put(route('services.update', $service), ['name' => 'X', 'duration_minutes' => 30, 'price' => 1])->assertNotFound();
        $this->delete(route('services.destroy', $service))->assertNotFound();
        $this->get(route('staff.edit', $member))->assertNotFound();
        $this->put(route('staff.schedule.update', $member), ['blocks' => []])->assertNotFound();
        $this->patch(route('agenda.status.update', $booking), ['status' => 'cancelled'])->assertNotFound();

        $this->assertModelExists($service);
        $this->assertSame('confirmed', $booking->fresh()->status->value);
    }

    public function test_staff_of_another_business_cannot_be_assigned_to_a_service()
    {
        $this->actingAsOwner();
        $foreign = StaffMember::factory()->for($this->otherBusiness)->create();

        $this->post(route('services.store'), [
            'name' => 'Corte',
            'duration_minutes' => 30,
            'price' => '50',
            'staff_member_ids' => [$foreign->id],
        ])->assertSessionHasErrors('staff_member_ids.0');

        $this->assertSame(0, Service::query()->where('name', 'Corte')->count());
    }

    public function test_new_records_belong_to_the_owners_business()
    {
        $business = $this->actingAsOwner();

        $this->post(route('staff.store'), ['name' => 'Lucía', 'is_active' => true]);

        $this->assertSame($business->id, StaffMember::query()->withoutGlobalScopes()->sole()->business_id);
    }
}
