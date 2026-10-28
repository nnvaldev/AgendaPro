<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Al cliente, cuando el negocio cancela su reserva desde la agenda.
 */
class BookingCancelledByBusiness extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        return $this->withDetails(
            $this->message()
                ->subject("Tu reserva en {$booking->business->name} fue cancelada")
                ->greeting('Hola, '.strtok($booking->customer_name, ' '))
                ->line("**{$booking->business->name}** tuvo que cancelar tu reserva. Lamentamos el inconveniente.")
        )
            ->action('Reservar otro horario', route('public.booking', $booking->business->slug))
            ->line('Si tienes dudas, responde este correo y le llegará directamente al negocio.');
    }
}
