<?php

namespace App\Http\Controllers\Panel;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Panel\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\WorkingHour;
use App\Tenancy\CurrentBusiness;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(CurrentBusiness $currentBusiness): Response
    {
        $business = $currentBusiness->getOrFail();
        $now = CarbonImmutable::now($business->timezone);
        $notCancelled = fn ($query) => $query->whereNot('status', BookingStatus::Cancelled->value);

        $between = fn (CarbonImmutable $from, CarbonImmutable $to) => Booking::query()
            ->where('starts_at', '>=', $from->utc())
            ->where('starts_at', '<', $to->utc());

        return Inertia::render('Dashboard', [
            'stats' => [
                'today' => $between($now->startOfDay(), $now->endOfDay())->tap($notCancelled)->count(),
                'next_7_days' => $between($now, $now->addDays(7))->blocking()->count(),
                'month_revenue_cents' => (int) $between($now->startOfMonth(), $now->endOfMonth())
                    ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
                    ->sum('price_cents'),
                'month_no_shows' => $between($now->startOfMonth(), $now->endOfMonth())
                    ->where('status', BookingStatus::NoShow->value)
                    ->count(),
            ],
            'upcoming' => BookingResource::collection(
                Booking::query()
                    ->blocking()
                    ->where('starts_at', '>=', $now->utc())
                    ->with(['service', 'staffMember'])
                    ->orderBy('starts_at')
                    ->limit(6)
                    ->get()
            )->resolve(),
            'setup' => [
                'services' => Service::query()->exists(),
                'staff' => StaffMember::query()->exists(),
                'schedule' => WorkingHour::query()
                    ->whereIn('staff_member_id', StaffMember::query()->select('id'))
                    ->exists(),
            ],
            'publicUrl' => route('public.booking', $business->slug),
        ]);
    }
}
