<?php

namespace App\Http\Requests\Field;

use Illuminate\Foundation\Http\FormRequest;

class RejectAttendanceLogRequest extends FormRequest
{
    /** Only the field supervisor at the log's organisation may review it. */
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('log'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['reason' => 'سبب الرفض'];
    }
}
