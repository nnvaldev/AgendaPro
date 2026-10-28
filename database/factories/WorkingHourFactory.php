<?php

namespace Database\Factories;

use App\Models\StaffMember;
use App\Models\WorkingHour;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkingHour>
 */
class WorkingHourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_member_id' => StaffMember::factory(),
            'weekday' => CarbonInterface::MONDAY,
            'starts_at' => '09:00',
            'ends_at' => '18:00',
        ];
    }
}
