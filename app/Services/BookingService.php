<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffMember;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * SQLSTATE de Postgres cuando se viola una restricción de exclusión.
     */
    private const EXCLUSION_VIOLATION = '23P01';

    public function __construct(private AvailabilityService $availability) {}

    /**
     * Reserva un horario. Sin profesional, asigna el primero que esté libre.
     *
     * @param  array{customer_name: string, customer_email: string, customer_phone?: ?string, notes?: ?string}  $customer
     *
     * @throws SlotUnavailableException
     */
    public function book(Service $service, CarbonInterface $startsAt, array $customer, ?StaffMember $staff = null): Booking
    {
        $startsAt = CarbonImmutable::instance($startsAt)->utc();
        $date = $startsAt->setTimezone($service->business->timezone)->toDateString();

        $candidates = $this->availability->slots($service, $date, $staff)
            ->filter(fn (array $slot) => $slot['starts_at']->equalTo($startsAt))
            ->pluck('staff_member_id');

        foreach ($candidates as $staffMemberId) {
            try {
                // La transacción crea un savepoint: si la restricción rechaza el
                // insert, la transacción externa sigue usable para el siguiente intento.
                return DB::transaction(fn () => Booking::create([
                    'business_id' => $service->business_id,
                    'service_id' => $service->id,
                    'staff_member_id' => $staffMemberId,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->addMinutes($service->duration_minutes),
                    'status' => BookingStatus::Confirmed,
                    'price_cents' => $service->price_cents,
                    'customer_name' => $customer['customer_name'],
                    'customer_email' => $customer['customer_email'],
                    'customer_phone' => $customer['customer_phone'] ?? null,
                    'notes' => $customer['notes'] ?? null,
                ]));
            } catch (QueryException $e) {
                // Otra reserva ganó la carrera por este horario; probamos el siguiente profesional.
                if ($e->getCode() !== self::EXCLUSION_VIOLATION) {
                    throw $e;
                }
            }
        }

        throw new SlotUnavailableException;
    }
}
