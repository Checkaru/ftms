<?php

namespace App\Http\Requests\Coordinator;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $creating = $userId === null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'student_number' => [
                'nullable',
                'string',
                'max:50',
                'required_if:role,'.UserRole::Student->value,
                Rule::unique('users', 'student_number')->ignore($userId),
            ],
            'organization_id' => [
                'nullable',
                'required_if:role,'.UserRole::FieldSupervisor->value,
                'exists:organizations,id',
            ],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', 'min:8'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.required_if' => 'المشرف الميداني يجب أن يُسند إلى مؤسسة.',
            'student_number.required_if' => 'الرقم الجامعي مطلوب للطالب.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'role' => 'الدور',
            'phone' => 'الهاتف',
            'student_number' => 'الرقم الجامعي',
            'organization_id' => 'المؤسسة',
            'password' => 'كلمة المرور',
            'is_active' => 'مُفعّل',
        ];
    }
}
