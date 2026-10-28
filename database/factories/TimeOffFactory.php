<?php

namespace Database\Factories;

use App\Models\StaffMember;
use App\Models\TimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeOff>
 */
class TimeOffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->utc()->addDay()->startOfHour();

        return [
            'staff_member_id' => StaffMember::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
            'reason' => fake()->randomElement(['Trámite personal', 'Capacitación', 'Vacaciones']),
        ];
    }
}
