<?php

namespace Tests\Feature\Booking;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesBookableBusiness;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use CreatesBookableBusiness, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToMondayMorning();
    }

    /**
     * @return list<string>
     */
    private function slotTimes(Business $business, Service $service, string $date = '2026-11-03', array $query = []): array
    {
        $response = $this->getJson(route('api.businesses.availability', [
            'business' => $business->slug,
            'service_id' => $service->id,
            'date' => $date,
            ...$query,
        ]))->assertOk();

        return collect($response->json('data'))->pluck('starts_at')->map(fn (string $iso) => substr($iso, 11, 5))->all();
    }

    public function test_public_profile_lists_active_services_and_staff()
    {
        [$business, $service, $staff] = $this->bookableBusiness();
        Service::factory()->for($business)->inactive()->create();

        $this->getJson(route('api.businesses.show', $business->slug))
            ->assertOk()
            ->assertJsonCount(1, 'data.services')
            ->assertJsonPath('data.services.0.id', $service->id)
            ->assertJsonPath('data.services.0.staff_member_ids', [$staff[0]->id])
            ->assertJsonPath('data.staff.0.name', $staff[0]->name);
    }

    public function test_slots_follow_working_hours_duration_and_interval()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->assertSame(
            ['09:00', '09:15', '09:30', '09:45', '10:00', '10:15', '10:30'],
            $this->slotTimes($business, $service),
        );
    }

    public function test_slots_are_returned_in_the_business_timezone()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->getJson(route('api.businesses.availability', [
            'business' => $business->slug,
            'service_id' => $service->id,
            'date' => '2026-11-03',
        ]))
            ->assertJsonPath('data.0.starts_at', '2026-11-03T09:00:00-04:00')
            ->assertJsonPath('meta.timezone', 'America/La_Paz');
    }

    public function test_days_without_working_hours_have_no_slots()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->assertSame([], $this->slotTimes($business, $service, '2026-11-04'));
    }

    public function test_active_bookings_block_overlapping_slots()
    {
        [$business, $service, $staff] = $this->bookableBusiness();

        Booking::factory()->create([
            'service_id' => $service->id,
            'staff_member_id' => $staff[0]->id,
            'starts_at' => $this->laPaz('2026-11-03 09:30')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 10:00')->utc(),
        ]);

        $this->assertSame(['09:00', '10:00', '10:15', '10:30'], $this->slotTimes($business, $service));
    }

    public function test_cancelled_bookings_do_not_block_slots()
    {
        [$business, $service, $staff] = $this->bookableBusiness();

        Booking::factory()->cancelled()->create([
            'service_id' => $service->id,
            'staff_member_id' => $staff[0]->id,
            'starts_at' => $this->laPaz('2026-11-03 09:30')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 10:00')->utc(),
        ]);

        $this->assertCount(7, $this->slotTimes($business, $service));
    }

    public function test_time_off_blocks_slots()
    {
        [$business, $service, $staff] = $this->bookableBusiness();

        $staff[0]->timeOffs()->create([
            'starts_at' => $this->laPaz('2026-11-03 10:00')->utc(),
            'ends_at' => $this->laPaz('2026-11-03 11:00')->utc(),
        ]);

        $this->assertSame(['09:00', '09:15', '09:30'], $this->slotTimes($business, $service));
    }

    public function test_slots_respect_minimum_notice()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->travelTo($this->laPaz('2026-11-03 09:10'));

        $this->assertSame(['10:15', '10:30'], $this->slotTimes($business, $service));
    }

    public function test_no_slots_beyond_the_booking_window()
    {
        [$business, $service] = $this->bookableBusiness();

        $this->assertSame([], $this->slotTimes($business, $service, '2026-12-15'));
    }

    public function test_slots_can_be_filtered_by_staff_member()
    {
        [$business, $service, $staff] = $this->bookableBusiness(staffCount: 2);

        $response = $this->getJson(route('api.businesses.availability', [
            'business' => $business->slug,
            'service_id' => $service->id,
            'staff_member_id' => $staff[1]->id,
            'date' => '2026-11-03',
        ]))->assertOk();

        $this->assertSame([$staff[1]->id], collect($response->json('data'))->pluck('staff_member_id')->unique()->values()->all());
    }

    public function test_staff_member_must_offer_the_service()
    {
        [$business, $service] = $this->bookableBusiness();
        $other = $business->staffMembers()->create(['name' => 'Sin servicios']);

        $this->getJson(route('api.businesses.availability', [
            'business' => $business->slug,
            'service_id' => $service->id,
            'staff_member_id' => $other->id,
            'date' => '2026-11-03',
        ]))->assertUnprocessable()->assertJsonValidationErrors('staff_member_id');
    }

    public function test_services_from_another_business_are_rejected()
    {
        [$business] = $this->bookableBusiness();
        [, $foreignService] = $this->bookableBusiness();

        $this->getJson(route('api.businesses.availability', [
            'business' => $business->slug,
            'service_id' => $foreignService->id,
            'date' => '2026-11-03',
        ]))->assertUnprocessable()->assertJsonValidationErrors('service_id');
    }
}
