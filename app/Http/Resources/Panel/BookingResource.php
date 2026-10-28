<?php

namespace App\Http\Resources\Panel;

use App\Models\Booking;
use App\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reserva tal como la ve el dueño en el panel (con datos de contacto).
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = app(CurrentBusiness::class)->getOrFail()->timezone;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'starts_at' => $this->starts_at->setTimezone($timezone)->toIso8601String(),
            'ends_at' => $this->ends_at->setTimezone($timezone)->toIso8601String(),
            'service' => $this->service->name,
            'staff_member_id' => $this->staff_member_id,
            'staff_member' => $this->staffMember->name,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'notes' => $this->notes,
            'price_cents' => $this->price_cents,
        ];
    }
}
