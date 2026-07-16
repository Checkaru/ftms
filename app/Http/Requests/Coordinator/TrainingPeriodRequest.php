<?php

namespace App\Http\Requests\Coordinator;

use Illuminate\Foundation\Http\FormRequest;

class TrainingPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_open' => $this->boolean('is_open')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'required_hours' => ['required', 'integer', 'min:1', 'max:2000'],
            'is_open' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الفترة',
            'starts_on' => 'تاريخ البداية',
            'ends_on' => 'تاريخ النهاية',
            'required_hours' => 'الساعات المطلوبة',
            'is_open' => 'مفتوحة',
        ];
    }
}
