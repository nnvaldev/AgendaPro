<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ServiceRequest;
use App\Models\Service;
use App\Models\StaffMember;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('services/Index', [
            'services' => Service::query()
                ->withCount(['staffMembers', 'bookings'])
                ->orderBy('name')
                ->get()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration_minutes' => $service->duration_minutes,
                    'price_cents' => $service->price_cents,
                    'is_active' => $service->is_active,
                    'staff_count' => $service->staff_members_count,
                    'bookings_count' => $service->bookings_count,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('services/Form', [
            'service' => null,
            'staff' => $this->staffOptions(),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->serviceAttributes());
        $service->staffMembers()->sync($request->staffMemberIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio creado.']);

        return to_route('services.index');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('services/Form', [
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'price' => number_format($service->price_cents / 100, 2, '.', ''),
                'is_active' => $service->is_active,
                'staff_member_ids' => $service->staffMembers()->pluck('staff_members.id'),
            ],
            'staff' => $this->staffOptions(),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->serviceAttributes());
        $service->staffMembers()->sync($request->staffMemberIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio actualizado.']);

        return to_route('services.index');
    }

    public function destroy(Service $service): RedirectResponse
    {
        // Las reservas guardan el historial y el precio cobrado: no se borran.
        if ($service->bookings()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Este servicio tiene reservas. Desactívalo en lugar de eliminarlo.']);

            return back();
        }

        $service->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Servicio eliminado.']);

        return to_route('services.index');
    }

    /**
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function staffOptions(): array
    {
        return StaffMember::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (StaffMember $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'is_active' => $member->is_active,
            ])
            ->all();
    }
}
