<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class AvailabilityRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
