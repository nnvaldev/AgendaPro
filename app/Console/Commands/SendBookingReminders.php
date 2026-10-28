<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingNotifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('bookings:send-reminders')]
#[Description('Envía el recordatorio a los clientes con reserva en las próximas 24 horas')]
class SendBookingReminders extends Command
{
    public function handle(BookingNotifier $notifier): int
    {
        $sent = 0;

        Booking::query()
            ->blocking()
            ->whereNull('reminder_sent_at')
            ->where('starts_at', '>', now())
            ->where('starts_at', '<=', now()->addDay())
            // Quien reservó con menos de un día de anticipación ya tiene fresca
            // la confirmación: un recordatorio extra sería ruido.
            ->whereRaw("created_at <= starts_at - interval '24 hours'")
            ->with(['business.owner', 'service', 'staffMember'])
            ->chunkById(100, function (Collection $bookings) use ($notifier, &$sent) {
                foreach ($bookings as $booking) {
                    // Se marca antes de encolar: si el comando se corta a la
                    // mitad, nadie recibe el recordatorio dos veces.
                    $booking->forceFill(['reminder_sent_at' => now()])->save();
                    $notifier->reminder($booking);
                    $sent++;
                }
            });

        $this->info("Recordatorios enviados: {$sent}");

        return self::SUCCESS;
    }
}
