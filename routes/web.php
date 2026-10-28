<?php

use App\Http\Controllers\Panel\AgendaController;
use App\Http\Controllers\Panel\BusinessSettingsController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\OnboardingController;
use App\Http\Controllers\Panel\ServiceController;
use App\Http\Controllers\Panel\StaffMemberController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Páginas públicas del cliente final (sin cuenta).
Route::get('reservar/{business:slug}', [PublicBookingController::class, 'show'])->name('public.booking');
Route::get('reserva/{booking:manage_token}', [PublicBookingController::class, 'manage'])
    ->whereUuid('booking')
    ->name('public.booking.manage');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('bienvenida', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('bienvenida', [OnboardingController::class, 'store'])->name('onboarding.store');

    // Panel del negocio: todo lo de aquí adentro queda filtrado por el negocio del usuario.
    Route::middleware('business')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::resource('servicios', ServiceController::class)
            ->except('show')
            ->parameters(['servicios' => 'service'])
            ->names('services');

        Route::resource('equipo', StaffMemberController::class)
            ->except('show')
            ->parameters(['equipo' => 'staffMember'])
            ->names('staff');
        Route::put('equipo/{staffMember}/horario', [StaffMemberController::class, 'updateSchedule'])
            ->name('staff.schedule.update');

        Route::get('agenda', [AgendaController::class, 'index'])->name('agenda.index');
        Route::patch('agenda/{booking}/estado', [AgendaController::class, 'updateStatus'])
            ->name('agenda.status.update');

        Route::get('negocio', [BusinessSettingsController::class, 'edit'])->name('business.edit');
        Route::patch('negocio', [BusinessSettingsController::class, 'update'])->name('business.update');
    });
});

require __DIR__.'/settings.php';
