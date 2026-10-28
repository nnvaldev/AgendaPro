<?php

namespace App\Http\Requests\Panel;

use App\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffMemberRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer', Rule::exists('services', 'id')
                ->where('business_id', app(CurrentBusiness::class)->id())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_ids.*' => 'servicio',
        ];
    }

    /**
     * @return array{name: string, email: string|null, is_active: bool}
     */
    public function staffAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'email' => $this->filled('email') ? $this->string('email')->trim()->toString() : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return list<int>
     */
    public function serviceIds(): array
    {
        return array_values(array_map('intval', $this->array('service_ids')));
    }
}
