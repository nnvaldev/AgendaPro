<?php

namespace App\Notifications;

use App\Support\BookingCalendar;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Al cliente, apenas reserva. Incluye el .ics y el link para gestionarla.
 */
class BookingConfirmed extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $firstName = strtok($booking->customer_name, ' ');

        return $this->withDetails(
            $this->message()
                ->subject("Reserva confirmada en {$booking->business->name}")
                ->greeting("¡Listo, {$firstName}!")
                ->line("Tu reserva en **{$booking->business->name}** está confirmada.")
        )
            ->action('Ver o cancelar mi reserva', route('public.booking.manage', $booking->manage_token))
            ->line('Adjuntamos el evento para que lo agregues a tu calendario.')
            ->attachData(BookingCalendar::ics($booking), 'reserva.ics', ['mime' => 'text/calendar']);
    }
}
