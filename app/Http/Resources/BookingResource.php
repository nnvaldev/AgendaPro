<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
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
        $timezone = $this->business->timezone;

        return [
            'status' => $this->status->value,
            'starts_at' => $this->starts_at->setTimezone($timezone)->toIso8601String(),
            'ends_at' => $this->ends_at->setTimezone($timezone)->toIso8601String(),
            'service' => $this->service->name,
            'staff_member' => $this->staffMember->name,
            'customer_name' => $this->customer_name,
            'price_cents' => $this->price_cents,
            'currency' => $this->business->currency,
            'business' => ['name' => $this->business->name, 'slug' => $this->business->slug],
            // El token de gestión solo se entrega a quien acaba de reservar.
            'manage_token' => $this->when($this->wasRecentlyCreated, $this->manage_token),
        ];
    }
}
