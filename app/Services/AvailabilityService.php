<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\TimeOff;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Horarios libres para un servicio en un día, por profesional.
     *
     * Los horarios se generan en la hora local del negocio, cada
     * `slot_interval_minutes` desde el inicio de cada bloque de atención, y se
     * descartan los que se cruzan con reservas activas o bloqueos de agenda.
     *
     * @param  string  $date  Fecha local del negocio (Y-m-d).
     * @return Collection<int, array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, staff_member_id: int}>
     */
    public function slots(Service $service, string $date, ?StaffMember $staff = null): Collection
    {
        $business = $service->business;
        $day = CarbonImmutable::createFromFormat('Y-m-d', $date, $business->timezone)->startOfDay();
        $now = CarbonImmutable::now($business->timezone);

        if ($day->lt($now->startOfDay()) || $day->gt($now->startOfDay()->addDays($business->booking_window_days))) {
            return collect();
        }

        $earliest = $now->addMinutes($business->min_notice_minutes);
        $dayStart = $day->utc();
        $dayEnd = $day->addDay()->utc();

        $staffMembers = $service->staffMembers()
            ->active()
            ->when($staff, fn ($query) => $query->whereKey($staff->id))
            ->with([
                'workingHours' => fn ($query) => $query->where('weekday', $day->dayOfWeek)->orderBy('starts_at'),
                'bookings' => fn ($query) => $query->blocking()->overlapping($dayStart, $dayEnd),
                'timeOffs' => fn ($query) => $query->where('starts_at', '<', $dayEnd)->where('ends_at', '>', $dayStart),
            ])
            ->orderBy('staff_members.id')
            ->get();

        $duration = $service->duration_minutes;
        $interval = max(1, $business->slot_interval_minutes);
        $slots = collect();

        foreach ($staffMembers as $member) {
            $busy = $member->bookings->map(fn (Booking $booking) => [$booking->starts_at, $booking->ends_at])
                ->concat($member->timeOffs->map(fn (TimeOff $timeOff) => [$timeOff->starts_at, $timeOff->ends_at]));

            foreach ($member->workingHours as $hours) {
                $blockEnd = $day->setTimeFromTimeString($hours->ends_at);

                for ($start = $day->setTimeFromTimeString($hours->starts_at); $start->addMinutes($duration)->lte($blockEnd); $start = $start->addMinutes($interval)) {
                    $end = $start->addMinutes($duration);

                    if ($start->lt($earliest) || $busy->contains(fn (array $range) => $start->lt($range[1]) && $end->gt($range[0]))) {
                        continue;
                    }

                    $slots->push(['starts_at' => $start, 'ends_at' => $end, 'staff_member_id' => $member->id]);
                }
            }
        }

        return $slots->sortBy(fn (array $slot) => $slot['starts_at']->getTimestamp())->values();
    }
}
