<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StaffMemberRequest;
use App\Http\Requests\Panel\StaffScheduleRequest;
use App\Models\Service;
use App\Models\StaffMember;
use App\Models\WorkingHour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StaffMemberController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('staff/Index', [
            'staff' => StaffMember::query()
                ->with(['services' => fn ($query) => $query->orderBy('name'), 'workingHours'])
                ->orderBy('name')
                ->get()
                ->map(fn (StaffMember $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'is_active' => $member->is_active,
                    'services' => $member->services->pluck('name'),
                    'working_days' => $member->workingHours->pluck('weekday')->unique()->sort()->values(),
                    'weekly_minutes' => $member->workingHours->sum(
                        fn (WorkingHour $hours) => (strtotime($hours->ends_at) - strtotime($hours->starts_at)) / 60,
                    ),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('staff/Form', [
            'member' => null,
            'services' => $this->serviceOptions(),
            'schedule' => [],
        ]);
    }

    public function store(StaffMemberRequest $request): RedirectResponse
    {
        $member = StaffMember::create($request->staffAttributes());
        $member->services()->sync($request->serviceIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesional agregado. Ahora define su horario.']);

        return to_route('staff.edit', $member);
    }

    public function edit(StaffMember $staffMember): Response
    {
        return Inertia::render('staff/Form', [
            'member' => [
                'id' => $staffMember->id,
                'name' => $staffMember->name,
                'email' => $staffMember->email,
                'is_active' => $staffMember->is_active,
                'service_ids' => $staffMember->services()->pluck('services.id'),
            ],
            'services' => $this->serviceOptions(),
            'schedule' => $staffMember->workingHours()
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get()
                ->map(fn (WorkingHour $hours) => [
                    'weekday' => $hours->weekday,
                    'starts_at' => substr($hours->starts_at, 0, 5),
                    'ends_at' => substr($hours->ends_at, 0, 5),
                ]),
        ]);
    }

    public function update(StaffMemberRequest $request, StaffMember $staffMember): RedirectResponse
    {
        $staffMember->update($request->staffAttributes());
        $staffMember->services()->sync($request->serviceIds());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos guardados.']);

        return to_route('staff.edit', $staffMember);
    }

    /**
     * Reemplaza el horario semanal completo. Las reservas ya tomadas se
     * mantienen: el nuevo horario solo afecta a la disponibilidad futura.
     */
    public function updateSchedule(StaffScheduleRequest $request, StaffMember $staffMember): RedirectResponse
    {
        DB::transaction(function () use ($request, $staffMember) {
            $staffMember->workingHours()->delete();
            $staffMember->workingHours()->createMany($request->blocks());
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario guardado.']);

        return to_route('staff.edit', $staffMember);
    }

    public function destroy(StaffMember $staffMember): RedirectResponse
    {
        if ($staffMember->bookings()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Este profesional tiene reservas. Desactívalo en lugar de eliminarlo.']);

            return back();
        }

        $staffMember->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Profesional eliminado.']);

        return to_route('staff.index');
    }

    /**
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function serviceOptions(): array
    {
        return Service::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'is_active' => $service->is_active,
            ])
            ->all();
    }
}
