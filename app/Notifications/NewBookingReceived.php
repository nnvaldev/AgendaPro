<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Al dueño, cada vez que entra una reserva desde la página pública.
 */
class NewBookingReceived extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        $message = $this->withDetails(
            (new MailMessage)
                ->subject("Nueva reserva: {$booking->customer_name}, {$this->when()}")
                ->greeting('¡Nueva reserva!')
                ->line("**{$booking->customer_name}** reservó en {$booking->business->name}.")
        )->line("**Contacto:** {$booking->customer_email}".($booking->customer_phone ? " · {$booking->customer_phone}" : ''));

        if ($booking->notes) {
            $message->line("**Nota:** {$booking->notes}");
        }

        return $message->action(
            'Ver en la agenda',
            route('agenda.index', ['date' => $booking->localStartsAt()->toDateString()]),
        );
    }
}
