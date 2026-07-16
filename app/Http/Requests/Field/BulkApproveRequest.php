<?php

namespace App\Http\Requests\Field;

use Illuminate\Foundation\Http\FormRequest;

class BulkApproveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Each log is authorised individually (per-org) in the controller.
        return $this->user()->isFieldSupervisor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:attendance_logs,id'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'لم تحدد أي سجل.'];
    }
}
