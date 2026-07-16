<?php

namespace App\Http\Requests\Coordinator;

use App\Enums\PlacementStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $placementId = $this->route('placement')?->id;

        return [
            'student_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Student->value),
                // One placement per student per period (also enforced by a DB unique).
                Rule::unique('placements')
                    ->where(fn ($q) => $q->where('period_id', $this->input('period_id')))
                    ->ignore($placementId),
            ],
            'organization_id' => ['required', 'exists:organizations,id'],
            'period_id' => ['required', 'exists:training_periods,id'],
            'field_supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', UserRole::FieldSupervisor->value),
            ],
            'academic_supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', UserRole::AcademicSupervisor->value),
            ],
            'status' => ['required', Rule::enum(PlacementStatus::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $fieldSupervisorId = $this->input('field_supervisor_id');

            if ($fieldSupervisorId) {
                $supervisor = User::find($fieldSupervisorId);

                // The field supervisor must belong to the placement's organisation,
                // otherwise the approval row-gate would never let them work.
                if ($supervisor && (int) $supervisor->organization_id !== (int) $this->input('organization_id')) {
                    $validator->errors()->add(
                        'field_supervisor_id',
                        'المشرف الميداني يجب أن يتبع نفس المؤسسة المختارة.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'student_id.unique' => 'هذا الطالب لديه تنسيب مسبق في هذه الفترة.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'الطالب',
            'organization_id' => 'المؤسسة',
            'period_id' => 'الفترة',
            'field_supervisor_id' => 'المشرف الميداني',
            'academic_supervisor_id' => 'المشرف الأكاديمي',
            'status' => 'الحالة',
        ];
    }
}
