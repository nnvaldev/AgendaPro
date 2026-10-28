<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessController;
use Illuminate\Support\Facades\Route;

// API pública de reservas: la consume la página de reserva del negocio.

Route::prefix('businesses/{business:slug}')->name('api.businesses.')->group(function () {
    Route::get('/', [BusinessController::class, 'show'])->name('show');
    Route::get('availability', [BusinessController::class, 'availability'])->name('availability');
    Route::post('bookings', [BookingController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('bookings.store');
});

Route::prefix('bookings/{booking:manage_token}')->whereUuid('booking')->name('api.bookings.')->group(function () {
    Route::get('/', [BookingController::class, 'show'])->name('show');
    Route::post('cancel', [BookingController::class, 'cancel'])
        ->middleware('throttle:10,1')
        ->name('cancel');
});
