<?php

namespace App\Http\Requests\Panel;

use App\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:1000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'staff_member_ids' => ['array'],
            'staff_member_ids.*' => ['integer', Rule::exists('staff_members', 'id')
                ->where('business_id', app(CurrentBusiness::class)->id())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'duration_minutes' => 'duración',
            'price' => 'precio',
            'staff_member_ids.*' => 'profesional',
        ];
    }

    /**
     * @return array{name: string, description: string|null, duration_minutes: int, price_cents: int, is_active: bool}
     */
    public function serviceAttributes(): array
    {
        return [
            'name' => $this->string('name')->trim()->toString(),
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
            'duration_minutes' => $this->integer('duration_minutes'),
            // El precio llega en bolivianos; se guarda en centavos para no arrastrar errores de punto flotante.
            'price_cents' => (int) round($this->float('price') * 100),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return list<int>
     */
    public function staffMemberIds(): array
    {
        return array_values(array_map('intval', $this->array('staff_member_ids')));
    }
}
