<?php

namespace App\Services;

use App\Models\Booking;
use App\Notifications\BookingCancelledByBusiness;
use App\Notifications\BookingCancelledByCustomer;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingReminder;
use App\Notifications\NewBookingReceived;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Decide quién recibe cada correo de una reserva. El cliente no tiene cuenta,
 * así que se le escribe directo a su email (notificación "on demand").
 *
 * Un fallo al encolar (p. ej. Redis caído) se reporta pero no se propaga: la
 * reserva ya está guardada y el cliente no debe ver un error por un correo.
 */
class BookingNotifier
{
    public function created(Booking $booking): void
    {
        $this->toCustomer($booking, new BookingConfirmed($booking));
        $this->toOwner($booking, new NewBookingReceived($booking));
    }

    public function cancelledByCustomer(Booking $booking): void
    {
        $this->toOwner($booking, new BookingCancelledByCustomer($booking));
    }

    public function cancelledByBusiness(Booking $booking): void
    {
        $this->toCustomer($booking, new BookingCancelledByBusiness($booking));
    }

    public function reminder(Booking $booking): void
    {
        $this->toCustomer($booking, new BookingReminder($booking));
    }

    private function toCustomer(Booking $booking, BaseNotification $notification): void
    {
        rescue(fn () => Notification::route('mail', [$booking->customer_email => $booking->customer_name])
            ->notify($notification));
    }

    private function toOwner(Booking $booking, BaseNotification $notification): void
    {
        rescue(fn () => $booking->business->owner?->notify($notification));
    }
}
