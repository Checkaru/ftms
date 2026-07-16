<?php

namespace App\Models;

use App\Enums\LogStatus;
use App\Enums\PlacementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id',
    'organization_id',
    'period_id',
    'field_supervisor_id',
    'academic_supervisor_id',
    'status',
])]
class Placement extends Model
{
    /** @use HasFactory<\Database\Factories\PlacementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => PlacementStatus::class,
        ];
    }

    // Relationships -----------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TrainingPeriod::class, 'period_id');
    }

    public function fieldSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'field_supervisor_id');
    }

    public function academicSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'academic_supervisor_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    // Progress ----------------------------------------------------------------

    /**
     * Minutes that count toward the requirement. ONLY approved logs. Nothing
     * anywhere sums pending or rejected minutes. See PROJECT.md.
     */
    public function approvedMinutes(): int
    {
        return (int) $this->attendanceLogs()
            ->where('status', LogStatus::Approved)
            ->sum('minutes');
    }

    /** Approved hours completed, rounded to one decimal for display. */
    public function approvedHours(): float
    {
        return round($this->approvedMinutes() / 60, 1);
    }

    /** Hours still required, floored at zero. */
    public function remainingHours(): float
    {
        $remaining = $this->period->required_hours - $this->approvedHours();

        return round(max(0, $remaining), 1);
    }

    /** Completion percentage (0–100), capped at 100. */
    public function percentComplete(): int
    {
        $required = $this->period->required_hours;

        if ($required <= 0) {
            return 0;
        }

        return (int) min(100, round($this->approvedHours() / $required * 100));
    }
}
