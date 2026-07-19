<?php

namespace App\Http\Requests\Field;

use App\Enums\EvaluationKind;
use Illuminate\Foundation\Http\FormRequest;

class FieldEvaluationRequest extends FormRequest
{
    /** Only a field supervisor at the placement's host organisation may grade it. */
    public function authorize(): bool
    {
        return $this->user()->can('submitFieldEvaluation', $this->route('placement'));
    }

    /**
     * Rules come from the rubric config; each score is bounded by its criterion.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'scores' => ['required', 'array'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($this->rubric() as $key => $meta) {
            $rules["scores.$key"] = ['required', 'integer', 'min:0', 'max:'.$meta['max']];
        }

        return $rules;
    }

    /**
     * @return array<string, array{label: string, max: int}>
     */
    public function rubric(): array
    {
        return config('training.rubrics.'.EvaluationKind::Field->value, []);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = ['comments' => 'الملاحظات'];

        foreach ($this->rubric() as $key => $meta) {
            $attributes["scores.$key"] = $meta['label'];
        }

        return $attributes;
    }
}
