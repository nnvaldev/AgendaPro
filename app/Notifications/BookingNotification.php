<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base de los correos de una reserva: se envían en cola y solo después de que
 * la transacción que creó o cambió la reserva se confirme.
 */
abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Correo con el negocio como remitente visible y respuesta al dueño.
     */
    protected function message(): MailMessage
    {
        $booking = $this->booking->loadMissing(['business.owner', 'service', 'staffMember']);
        $business = $booking->business;

        $message = (new MailMessage)->from(
            (string) config('mail.from.address'),
            "{$business->name} vía AgendaPro",
        );

        if ($business->owner !== null) {
            $message->replyTo($business->owner->email, $business->name);
        }

        return $message;
    }

    /**
     * Resumen de la reserva en el cuerpo del correo.
     */
    protected function withDetails(MailMessage $message): MailMessage
    {
        $booking = $this->booking;

        return $message
            ->line("**Servicio:** {$booking->service->name}")
            ->line("**Con:** {$booking->staffMember->name}")
            ->line('**Cuándo:** '.$this->when())
            ->line('**Total:** '.$this->price());
    }

    /**
     * "martes 3 de noviembre, 09:00".
     */
    protected function when(): string
    {
        return $this->booking->localStartsAt()->translatedFormat('l j \d\e F, H:i');
    }

    protected function price(): string
    {
        $amount = number_format($this->booking->price_cents / 100, 2, ',', '.');

        return $this->booking->business->currency === 'BOB' ? "Bs {$amount}" : "{$amount} {$this->booking->business->currency}";
    }
}
