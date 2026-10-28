<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\BusinessRequest;
use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Primer paso después del registro: crear el negocio.
 */
class OnboardingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->business !== null) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/Create');
    }

    public function store(BusinessRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->business === null) {
            $name = $request->string('name')->trim()->toString();

            $user->business()->create([
                'name' => $name,
                'slug' => Business::uniqueSlug($name),
                'phone' => $request->validated('phone'),
                'address' => $request->validated('address'),
            ]);

            Inertia::flash('toast', ['type' => 'success', 'message' => '¡Tu negocio está listo! Empieza agregando tus servicios.']);
        }

        return to_route('dashboard');
    }
}
