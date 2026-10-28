<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\BusinessRequest;
use App\Tenancy\CurrentBusiness;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function edit(CurrentBusiness $currentBusiness): Response
    {
        $business = $currentBusiness->getOrFail();

        return Inertia::render('business/Edit', [
            'business' => $business->only([
                'name',
                'slug',
                'phone',
                'address',
                'timezone',
                'currency',
                'slot_interval_minutes',
                'min_notice_minutes',
                'booking_window_days',
            ]),
            'slotIntervals' => BusinessRequest::SLOT_INTERVALS,
            'publicUrl' => route('public.booking', $business->slug),
        ]);
    }

    public function update(BusinessRequest $request, CurrentBusiness $currentBusiness): RedirectResponse
    {
        $currentBusiness->getOrFail()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Configuración guardada.']);

        return to_route('business.edit');
    }
}
