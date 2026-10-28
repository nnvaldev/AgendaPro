<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Al cliente, unas 24 horas antes (comando bookings:send-reminders).
 */
class BookingReminder extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;

        return $this->withDetails(
            $this->message()
                ->subject("Recordatorio: {$booking->service->name}, {$this->when()}")
                ->greeting('¡Te esperamos pronto!')
                ->line("Te recordamos tu reserva en **{$booking->business->name}**.")
        )
            ->action('Ver o cancelar mi reserva', route('public.booking.manage', $booking->manage_token))
            ->line('Si no puedes asistir, cancélala desde el link para liberar el horario.');
    }
}
