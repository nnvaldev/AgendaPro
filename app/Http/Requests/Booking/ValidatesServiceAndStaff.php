<?php

namespace App\Http\Requests\Booking;

use App\Models\Business;
use App\Models\Service;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas compartidas: el servicio y el profesional deben pertenecer al negocio
 * de la URL, estar activos, y el profesional debe realizar ese servicio.
 */
trait ValidatesServiceAndStaff
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function serviceAndStaffRules(): array
    {
        $business = $this->business();

        return [
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')
                ->where('business_id', $business->id)
                ->where('is_active', true)],
            'staff_member_id' => ['nullable', 'integer', Rule::exists('staff_members', 'id')
                ->where('business_id', $business->id)
                ->where('is_active', true)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || ! $this->filled('staff_member_id')) {
                    return;
                }

                $offersService = Service::whereKey($this->integer('service_id'))
                    ->whereHas('staffMembers', fn ($query) => $query->whereKey($this->integer('staff_member_id')))
                    ->exists();

                if (! $offersService) {
                    $validator->errors()->add('staff_member_id', 'Este profesional no realiza el servicio elegido.');
                }
            },
        ];
    }

    protected function business(): Business
    {
        $business = $this->route('business');
        assert($business instanceof Business);

        return $business;
    }
}
