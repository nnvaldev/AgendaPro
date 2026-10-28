<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    use ValidatesServiceAndStaff;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->serviceAndStaffRules(),
            // ISO 8601 con zona horaria, tal como lo devuelve el endpoint de disponibilidad.
            'starts_at' => ['required', 'date'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array{customer_name: string, customer_email: string, customer_phone: string|null, notes: string|null}
     */
    public function customer(): array
    {
        return [
            'customer_name' => $this->string('customer_name')->toString(),
            'customer_email' => $this->string('customer_email')->toString(),
            'customer_phone' => $this->filled('customer_phone') ? $this->string('customer_phone')->toString() : null,
            'notes' => $this->filled('notes') ? $this->string('notes')->toString() : null,
        ];
    }
}
