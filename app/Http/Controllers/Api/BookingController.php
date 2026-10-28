<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Business;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    /**
     * Reserva pública: el cliente no necesita cuenta.
     */
    public function store(StoreBookingRequest $request, Business $business, BookingService $bookings): JsonResponse
    {
        $service = $business->services()->findOrFail($request->integer('service_id'));
        $staff = $request->filled('staff_member_id')
            ? $business->staffMembers()->findOrFail($request->integer('staff_member_id'))
            : null;

        $booking = $bookings->book(
            $service,
            CarbonImmutable::parse($request->validated('starts_at')),
            $request->customer(),
            $staff,
        );

        return (new BookingResource($booking->load(['business', 'service', 'staffMember'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Consulta de una reserva con su token de gestión (link del email).
     */
    public function show(Booking $booking): BookingResource
    {
        return new BookingResource($booking->load(['business', 'service', 'staffMember']));
    }

    public function cancel(Booking $booking): BookingResource
    {
        abort_unless($booking->isCancellable(), 409, 'Esta reserva ya no se puede cancelar.');

        $booking->cancel();

        return new BookingResource($booking->load(['business', 'service', 'staffMember']));
    }
}
