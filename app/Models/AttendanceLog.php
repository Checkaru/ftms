<?php

namespace App\Models;

use App\Enums\LogStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// ONLY these four fields are ever accepted from a request. status, minutes,
// reviewed_by, reviewed_at, reject_reason and placement_id are set exclusively
// inside controller/action code — never by mass assignment. This is the single
// most important line in the app; see PROJECT.md → Security.
#[Fillable(['work_date', 'check_in', 'check_out', 'tasks'])]
class AttendanceLog extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceLogFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'minutes' => 'integer',
            'status' => LogStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Minutes are derived from check-in/out and stored once, on every save.
     * The client never sends minutes; storing an integer avoids float drift
     * across a 180-hour total. See PROJECT.md → Modelling rules.
     */
    protected static function booted(): void
    {
        static::saving(function (AttendanceLog $log): void {
            $log->minutes = $log->computeMinutes();
        });
    }

    public function computeMinutes(): int
    {
        $in = Carbon::createFromTimeString($this->check_in);
        $out = Carbon::createFromTimeString($this->check_out);

        // Field-training days are same-day; check_out is validated after check_in.
        if ($out->lessThanOrEqualTo($in)) {
            return 0;
        }

        return (int) round($in->diffInMinutes($out));
    }

    /** Hours for this single entry, one decimal. */
    protected function hours(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round($this->minutes / 60, 1),
        );
    }

    // Relationships -----------------------------------------------------------

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Review transitions ------------------------------------------------------
    // status / reviewed_* are set only here and in controllers, never from a
    // request. The reviewer is always the acting field supervisor.

    public function approveBy(User $reviewer): void
    {
        $this->status = LogStatus::Approved;
        $this->reviewed_by = $reviewer->id;
        $this->reviewed_at = now();
        $this->reject_reason = null;
        $this->save();
    }

    public function rejectBy(User $reviewer, string $reason): void
    {
        $this->status = LogStatus::Rejected;
        $this->reviewed_by = $reviewer->id;
        $this->reviewed_at = now();
        $this->reject_reason = $reason;
        $this->save();
    }

    /** Send an approved entry back for correction. Only a supervisor may do this. */
    public function revertBy(User $reviewer): void
    {
        $this->status = LogStatus::Pending;
        $this->reviewed_by = null;
        $this->reviewed_at = null;
        $this->reject_reason = null;
        $this->save();
    }

    // State helpers -----------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === LogStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === LogStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === LogStatus::Rejected;
    }

    /** Approved entries are locked to the student; only a supervisor reverts them. */
    public function isEditableByStudent(): bool
    {
        return $this->status !== LogStatus::Approved;
    }
}
