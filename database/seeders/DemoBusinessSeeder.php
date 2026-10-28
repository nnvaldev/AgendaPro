<?php

namespace Database\Seeders;

use App\Exceptions\SlotUnavailableException;
use App\Models\Business;
use App\Models\User;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Negocio de demo para la página pública y las cuentas de prueba del README.
 */
class DemoBusinessSeeder extends Seeder
{
    public function run(BookingService $bookings): void
    {
        if (Business::where('slug', 'barberia-el-prado')->exists()) {
            return;
        }

        $owner = User::updateOrCreate(
            ['email' => 'demo@agendapro.test'],
            ['name' => 'Dueño Demo', 'password' => Hash::make('password'), 'email_verified_at' => now()],
        );

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Barbería El Prado',
            'slug' => 'barberia-el-prado',
            'phone' => '+591 70000000',
            'address' => 'Av. 16 de Julio (El Prado), La Paz',
        ]);

        $services = collect([
            ['name' => 'Corte clásico', 'duration_minutes' => 30, 'price_cents' => 5000],
            ['name' => 'Corte + barba', 'duration_minutes' => 45, 'price_cents' => 8000],
            ['name' => 'Afeitado con toalla caliente', 'duration_minutes' => 30, 'price_cents' => 4000],
            ['name' => 'Perfilado de cejas', 'duration_minutes' => 15, 'price_cents' => 2000],
        ])->mapWithKeys(fn (array $data) => [$data['name'] => $business->services()->create($data)]);

        $staff = [
            'Carlos Mamani' => $services->keys()->all(),
            'Lucía Quispe' => ['Corte clásico', 'Perfilado de cejas'],
        ];

        foreach ($staff as $name => $serviceNames) {
            $member = $business->staffMembers()->create(['name' => $name]);
            $member->services()->attach($services->only($serviceNames)->pluck('id'));

            // Lunes a sábado, con pausa de almuerzo.
            foreach (range(CarbonInterface::MONDAY, CarbonInterface::SATURDAY) as $weekday) {
                $member->workingHours()->createMany([
                    ['weekday' => $weekday, 'starts_at' => '09:00', 'ends_at' => '13:00'],
                    ['weekday' => $weekday, 'starts_at' => '14:00', 'ends_at' => '19:00'],
                ]);
            }
        }

        // Algunas reservas el próximo día hábil, para que la agenda no se vea vacía.
        $day = CarbonImmutable::now($business->timezone)->addDay();
        if ($day->isSunday()) {
            $day = $day->addDay();
        }

        foreach (['10:00', '11:30', '15:00'] as $i => $time) {
            try {
                $bookings->book(
                    $services['Corte clásico'],
                    $day->setTimeFromTimeString($time),
                    ['customer_name' => "Cliente Demo {$i}", 'customer_email' => "cliente{$i}@agendapro.test"],
                );
            } catch (SlotUnavailableException) {
                // El horario ya pasó o está tomado; para la demo no importa.
            }
        }
    }
}
