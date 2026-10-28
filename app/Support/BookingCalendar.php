<?php

namespace App\Support;

use App\Models\Booking;

/**
 * Evento iCalendar (RFC 5545) para que el cliente agregue la reserva a su
 * calendario (Google, Outlook, Apple) desde el adjunto del correo.
 */
class BookingCalendar
{
    public static function ics(Booking $booking): string
    {
        $business = $booking->business;
        $format = fn ($date) => $date->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AgendaPro//Reservas//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:'.$booking->manage_token.'@agendapro',
            'DTSTAMP:'.$format(now()),
            'DTSTART:'.$format($booking->starts_at),
            'DTEND:'.$format($booking->ends_at),
            'SUMMARY:'.self::escape("{$booking->service->name} en {$business->name}"),
            'DESCRIPTION:'.self::escape("Con {$booking->staffMember->name}. Gestiona tu reserva: ".route('public.booking.manage', $booking->manage_token)),
            ...($business->address ? ['LOCATION:'.self::escape($business->address)] : []),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $text);
    }
}
