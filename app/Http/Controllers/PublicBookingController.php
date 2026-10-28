<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Http\Resources\BusinessResource;
use App\Models\Booking;
use App\Models\Business;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Páginas públicas para el cliente final. La reserva en sí la hace el
 * frontend contra la API pública (/api/businesses/...).
 */
class PublicBookingController extends Controller
{
    public function show(Business $business): Response
    {
        return Inertia::render('public/Book', [
            // Mismo JSON que GET /api/businesses/{slug} (resolve() dejaría las
            // colecciones anidadas envueltas en "data").
            'business' => (new BusinessResource($business->loadBookableCatalog()))
                ->response()
                ->getData(true)['data'],
            'today' => CarbonImmutable::now($business->timezone)->toDateString(),
            'bookingWindowDays' => $business->booking_window_days,
        ]);
    }

    public function manage(Booking $booking): Response
    {
        return Inertia::render('public/ManageBooking', [
            'booking' => (new BookingResource($booking->load(['business', 'service', 'staffMember'])))->resolve(),
            'token' => $booking->manage_token,
            'cancellable' => $booking->isCancellable(),
        ]);
    }
}
