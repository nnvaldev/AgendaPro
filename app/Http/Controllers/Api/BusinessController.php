<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AvailabilityRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class BusinessController extends Controller
{
    /**
     * Perfil público del negocio con sus servicios y profesionales activos.
     */
    public function show(Business $business): BusinessResource
    {
        return new BusinessResource($business->loadBookableCatalog());
    }

    /**
     * Horarios libres de un servicio en una fecha local del negocio.
     */
    public function availability(AvailabilityRequest $request, Business $business, AvailabilityService $availability): JsonResponse
    {
        $service = $business->services()->findOrFail($request->integer('service_id'));
        $staff = $request->filled('staff_member_id')
            ? $business->staffMembers()->findOrFail($request->integer('staff_member_id'))
            : null;

        $slots = $availability->slots($service, $request->validated('date'), $staff);

        return response()->json([
            'data' => $slots->map(fn (array $slot) => [
                'starts_at' => $slot['starts_at']->toIso8601String(),
                'ends_at' => $slot['ends_at']->toIso8601String(),
                'staff_member_id' => $slot['staff_member_id'],
            ]),
            'meta' => [
                'date' => $request->validated('date'),
                'timezone' => $business->timezone,
            ],
        ]);
    }
}
