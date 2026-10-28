<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos del negocio: alta en el onboarding y edición en la configuración.
 */
class BusinessRequest extends FormRequest
{
    /**
     * Intervalos de agenda que ofrece la configuración, en minutos.
     */
    public const SLOT_INTERVALS = [5, 10, 15, 20, 30, 60];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->isMethod('post')) {
            return $rules;
        }

        return [
            ...$rules,
            'slot_interval_minutes' => ['required', 'integer', 'in:'.implode(',', self::SLOT_INTERVALS)],
            'min_notice_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'booking_window_days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del negocio',
            'slot_interval_minutes' => 'intervalo de la agenda',
            'min_notice_minutes' => 'anticipación mínima',
            'booking_window_days' => 'ventana de reserva',
        ];
    }
}
