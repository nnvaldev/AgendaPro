<?php

namespace Tests\Feature\Panel;

use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ActsAsBusinessOwner;
use Tests\TestCase;

class StaffScheduleTest extends TestCase
{
    use ActsAsBusinessOwner, RefreshDatabase;

    public function test_schedule_is_replaced_with_the_new_blocks()
    {
        $business = $this->actingAsOwner();
        $member = StaffMember::factory()->for($business)->create();
        $member->workingHours()->create(['weekday' => 0, 'starts_at' => '10:00', 'ends_at' => '12:00']);

        $this->put(route('staff.schedule.update', $member), [
            'blocks' => [
                ['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '13:00'],
                ['weekday' => 1, 'starts_at' => '14:00', 'ends_at' => '19:00'],
            ],
        ])->assertRedirect(route('staff.edit', $member));

        $this->assertSame(
            [[1, '09:00:00', '13:00:00'], [1, '14:00:00', '19:00:00']],
            $member->workingHours()->orderBy('starts_at')->get()
                ->map(fn ($hours) => [$hours->weekday, $hours->starts_at, $hours->ends_at])
                ->all(),
        );
    }

    public function test_an_empty_schedule_clears_all_blocks()
    {
        $business = $this->actingAsOwner();
        $member = StaffMember::factory()->for($business)->create();
        $member->workingHours()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '18:00']);

        $this->put(route('staff.schedule.update', $member), ['blocks' => []])->assertSessionHasNoErrors();

        $this->assertSame(0, $member->workingHours()->count());
    }

    public function test_blocks_must_end_after_they_start()
    {
        $business = $this->actingAsOwner();
        $member = StaffMember::factory()->for($business)->create();

        $this->put(route('staff.schedule.update', $member), [
            'blocks' => [['weekday' => 1, 'starts_at' => '18:00', 'ends_at' => '09:00']],
        ])->assertSessionHasErrors('blocks.0.ends_at');
    }

    public function test_overlapping_blocks_on_the_same_day_are_rejected()
    {
        $business = $this->actingAsOwner();
        $member = StaffMember::factory()->for($business)->create();

        $this->put(route('staff.schedule.update', $member), [
            'blocks' => [
                ['weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '13:00'],
                ['weekday' => 2, 'starts_at' => '12:00', 'ends_at' => '15:00'],
                ['weekday' => 3, 'starts_at' => '12:00', 'ends_at' => '15:00'],
            ],
        ])->assertSessionHasErrors('blocks');

        $this->assertSame(0, $member->workingHours()->count());
    }
}
