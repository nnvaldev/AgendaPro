<?php

namespace App\Http\Resources;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Business
 */
class BusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'phone' => $this->phone,
            'address' => $this->address,
            'services' => ServiceResource::collection($this->whenLoaded('services')),
            'staff' => StaffMemberResource::collection($this->whenLoaded('staffMembers')),
        ];
    }
}
