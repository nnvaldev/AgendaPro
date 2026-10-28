<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Horario semanal completo de un profesional: reemplaza todos sus bloques.
 */
class StaffScheduleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'blocks' => ['present', 'array', 'max:50'],
            'blocks.*.weekday' => ['required', 'integer', 'between:0,6'],
            'blocks.*.starts_at' => ['required', 'date_format:H:i'],
            'blocks.*.ends_at' => ['required', 'date_format:H:i', 'after:blocks.*.starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'blocks.*.starts_at' => 'hora de inicio',
            'blocks.*.ends_at' => 'hora de fin',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach (collect($this->blocks())->groupBy('weekday') as $blocks) {
                    $sorted = $blocks->sortBy('starts_at')->values();

                    for ($i = 1; $i < $sorted->count(); $i++) {
                        if ($sorted[$i]['starts_at'] < $sorted[$i - 1]['ends_at']) {
                            $validator->errors()->add('blocks', 'Hay bloques que se cruzan en el mismo día.');

                            return;
                        }
                    }
                }
            },
        ];
    }

    /**
     * @return list<array{weekday: int, starts_at: string, ends_at: string}>
     */
    public function blocks(): array
    {
        /** @var array<int, array{weekday: int|string, starts_at: string, ends_at: string}> $blocks */
        $blocks = $this->array('blocks');

        return array_values(array_map(fn (array $block) => [
            'weekday' => (int) $block['weekday'],
            'starts_at' => $block['starts_at'],
            'ends_at' => $block['ends_at'],
        ], $blocks));
    }
}
