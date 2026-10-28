<?php

namespace Tests\Concerns;

use App\Models\Business;
use App\Models\Service;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

trait CreatesBookableBusiness
{
    /**
     * Lunes 2 de noviembre de 2026, 08:00 en La Paz (12:00 UTC).
     */
    protected function travelToMondayMorning(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-02 08:00', 'America/La_Paz'));
    }

    /**
     * Negocio con un servicio de 30 min y profesionales que atienden
     * los martes de 09:00 a 11:00, con horarios cada 15 min.
     *
     * @return array{0: Business, 1: Service, 2: Collection<int, StaffMember>}
     */
    protected function bookableBusiness(int $staffCount = 1): array
    {
        $business = Business::factory()->create([
            'slot_interval_minutes' => 15,
            'min_notice_minutes' => 60,
            'booking_window_days' => 30,
        ]);

        $service = Service::factory()->for($business)->create([
            'duration_minutes' => 30,
            'price_cents' => 5000,
        ]);

        $staff = StaffMember::factory()->count($staffCount)->for($business)->create()
            ->each(function (StaffMember $member) use ($service) {
                $member->services()->attach($service);
                $member->workingHours()->create([
                    'weekday' => CarbonInterface::TUESDAY,
                    'starts_at' => '09:00',
                    'ends_at' => '11:00',
                ]);
            });

        return [$business, $service, $staff];
    }

    /**
     * Instante en La Paz, para comparar con lo que guarda la base (UTC).
     */
    protected function laPaz(string $datetime): CarbonImmutable
    {
        return CarbonImmutable::parse($datetime, 'America/La_Paz');
    }
}
