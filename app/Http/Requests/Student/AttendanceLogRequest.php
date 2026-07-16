<?php

namespace App\Http\Requests\Student;

use App\Models\AttendanceLog;
use App\Models\Placement;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AttendanceLogRequest extends FormRequest
{
    private ?Placement $resolved = null;

    /**
     * Authorise at the row level before any validation runs. Creating: the
     * student must have an active placement they may log against. Editing: the
     * log must be theirs and not yet approved (an approved log is locked).
     */
    public function authorize(): bool
    {
        $log = $this->route('log');

        if ($log !== null) {
            return $this->user()->can('update', $log);
        }

        $placement = $this->targetPlacement();

        return $placement !== null && $this->user()->can('logAttendance', $placement);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $placement = $this->targetPlacement();
        $period = $placement->period;
        $logId = $this->route('log')?->id;

        // Within the period, and never in the future.
        $earliest = $period->starts_on->toDateString();
        $latest = ($period->ends_on->isPast() ? $period->ends_on : Carbon::today())->toDateString();

        return [
            'work_date' => [
                'required', 'date',
                'after_or_equal:'.$earliest,
                'before_or_equal:'.$latest,
            ],
            'check_in' => ['required', 'date_format:H:i'],
            'check_out' => ['required', 'date_format:H:i', 'after:check_in'],
            'tasks' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * One entry per day per placement. Checked with whereDate so it is robust
     * to the stored value carrying a 00:00 time component. The DB unique index
     * is the ultimate guard against a double-submit race.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('work_date')) {
                return;
            }

            $placement = $this->targetPlacement();
            $logId = $this->route('log')?->id;

            $duplicate = AttendanceLog::where('placement_id', $placement->id)
                ->whereDate('work_date', $this->input('work_date'))
                ->when($logId, fn ($q) => $q->whereKeyNot($logId))
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('work_date', 'لديك سجل مسبق في هذا التاريخ.');
            }
        });
    }

    /**
     * The placement a log is being written against: the edited log's placement,
     * otherwise the student's active placement.
     */
    public function targetPlacement(): ?Placement
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $log = $this->route('log');

        return $this->resolved = $log
            ? $log->placement->loadMissing('period')
            : $this->user()->activePlacement();
    }

    public function messages(): array
    {
        return [
            'work_date.unique' => 'لديك سجل مسبق في هذا التاريخ.',
            'work_date.after_or_equal' => 'التاريخ خارج فترة التدريب.',
            'work_date.before_or_equal' => 'لا يمكن تسجيل تاريخ في المستقبل أو خارج الفترة.',
            'check_out.after' => 'وقت الانصراف يجب أن يكون بعد وقت الحضور.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'work_date' => 'تاريخ اليوم',
            'check_in' => 'وقت الحضور',
            'check_out' => 'وقت الانصراف',
            'tasks' => 'المهام المنجزة',
        ];
    }
}
