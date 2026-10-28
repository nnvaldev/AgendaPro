<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Las fechas se pasan en UTC: es como se guardan en la base.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'business_id' => fn (array $attributes) => Service::query()->whereKey($attributes['service_id'])->value('business_id'),
            'staff_member_id' => fn (array $attributes) => StaffMember::factory()->state([
                'business_id' => $attributes['business_id'],
            ]),
            'starts_at' => CarbonImmutable::now('UTC')->addDay()->setTime(14, 0),
            'ends_at' => fn (array $attributes) => CarbonImmutable::instance($attributes['starts_at'])->addMinutes(30),
            'status' => BookingStatus::Confirmed,
            'price_cents' => 5000,
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Cancelled,
        ]);
    }
}
