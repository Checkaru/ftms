<?php

namespace App\Http\Requests\Academic;

use App\Enums\EvaluationKind;
use Illuminate\Foundation\Http\FormRequest;

class AcademicEvaluationRequest extends FormRequest
{
    /** Only the assigned academic supervisor may grade this placement. */
    public function authorize(): bool
    {
        return $this->user()->can('submitAcademicEvaluation', $this->route('placement'));
    }

    /**
     * Rules are built from the rubric config so criteria can change per period
     * without touching code. Each score is bounded by its criterion's max.
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
        return config('training.rubrics.'.EvaluationKind::Academic->value, []);
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
