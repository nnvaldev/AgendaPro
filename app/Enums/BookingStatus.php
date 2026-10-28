<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /**
     * Estados que ocupan el horario del profesional.
     *
     * Debe coincidir con el WHERE de la restricción `bookings_staff_no_overlap`.
     *
     * @return list<self>
     */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed];
    }

    public function blocksSlot(): bool
    {
        return in_array($this, self::blocking(), true);
    }

    /**
     * Cancelada es final: volver a activarla podría chocar con otra reserva
     * que ya tomó ese horario. Completada y "no asistió" se pueden corregir
     * entre sí.
     */
    public function canTransitionTo(self $next): bool
    {
        if ($next === $this) {
            return false;
        }

        return match ($this) {
            self::Pending => true,
            self::Confirmed => $next !== self::Pending,
            self::Completed, self::NoShow => in_array($next, [self::Completed, self::NoShow], true),
            self::Cancelled => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmada',
            self::Cancelled => 'Cancelada',
            self::Completed => 'Completada',
            self::NoShow => 'No asistió',
        };
    }
}
