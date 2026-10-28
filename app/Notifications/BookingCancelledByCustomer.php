<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Al dueño, cuando el cliente cancela desde su link: el horario quedó libre.
 */
class BookingCancelledByCustomer extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        return $this->withDetails(
            (new MailMessage)
                ->subject("{$booking->customer_name} canceló su reserva")
                ->greeting('Reserva cancelada')
                ->line("**{$booking->customer_name}** canceló su reserva. El horario volvió a quedar disponible.")
        )->action(
            'Ver en la agenda',
            route('agenda.index', ['date' => $booking->localStartsAt()->toDateString()]),
        );
    }
}
