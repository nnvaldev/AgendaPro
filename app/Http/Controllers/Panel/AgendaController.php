<?php

namespace App\Http\Controllers\Panel;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Panel\BookingResource;
use App\Models\Booking;
use App\Models\StaffMember;
use App\Services\BookingNotifier;
use App\Tenancy\CurrentBusiness;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    /**
     * Reservas de un día (hora local del negocio), de todos los estados.
     */
    public function index(Request $request, CurrentBusiness $currentBusiness): Response
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $timezone = $currentBusiness->getOrFail()->timezone;
        $day = $request->filled('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->string('date')->toString(), $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();

        $bookings = Booking::query()
            ->where('starts_at', '>=', $day->utc())
            ->where('starts_at', '<', $day->addDay()->utc())
            ->with(['service', 'staffMember'])
            ->orderBy('starts_at')
            ->get();

        return Inertia::render('agenda/Index', [
            'date' => $day->toDateString(),
            'today' => CarbonImmutable::now($timezone)->toDateString(),
            'bookings' => BookingResource::collection($bookings)->resolve(),
            'staff' => StaffMember::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (StaffMember $member) => ['id' => $member->id, 'name' => $member->name]),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking, BookingNotifier $notifier): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);

        $status = BookingStatus::from($validated['status']);

        if (! $booking->status->canTransitionTo($status)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "Una reserva {$booking->status->label()} no puede pasar a {$status->label()}.",
            ]);

            return back();
        }

        $booking->transitionTo($status);

        if ($status === BookingStatus::Cancelled) {
            $notifier->cancelledByBusiness($booking);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Reserva marcada como {$status->label()}."]);

        return back();
    }
}
