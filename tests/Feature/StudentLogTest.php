<?php

namespace Tests\Feature;

use App\Enums\LogStatus;
use App\Enums\PlacementStatus;
use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Placement, 2: User}  [student, placement, fieldSupervisor]
     */
    private function studentWithActivePlacement(): array
    {
        $org = Organization::factory()->create();
        $period = TrainingPeriod::factory()->open()->create();
        $student = User::factory()->student()->create();
        $field = User::factory()->fieldSupervisor($org)->create();

        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'organization_id' => $org->id,
            'period_id' => $period->id,
            'field_supervisor_id' => $field->id,
            'status' => PlacementStatus::Active,
        ]);

        return [$student, $placement, $field];
    }

    private function workDate(): string
    {
        return now()->subDays(3)->format('Y-m-d');
    }

    /** The trust model: a student cannot promote their own log via mass assignment. */
    public function test_student_cannot_set_status_or_minutes_via_mass_assignment(): void
    {
        [$student, $placement, $field] = $this->studentWithActivePlacement();

        $this->actingAs($student)->post(route('student.logs.store'), [
            'work_date' => $this->workDate(),
            'check_in' => '09:00',
            'check_out' => '15:00',
            'tasks' => 'مهام اليوم',
            // Hostile extras that must be ignored:
            'status' => LogStatus::Approved->value,
            'minutes' => 99999,
            'reviewed_by' => $field->id,
        ])->assertRedirect(route('student.dashboard'));

        $log = AttendanceLog::firstOrFail();
        $this->assertSame(LogStatus::Pending, $log->status);
        $this->assertSame(360, $log->minutes); // 09:00–15:00, computed server-side
        $this->assertNull($log->reviewed_by);
    }

    public function test_duplicate_work_date_is_rejected_by_validation(): void
    {
        [$student, $placement] = $this->studentWithActivePlacement();
        $date = $this->workDate();

        AttendanceLog::factory()->for($placement)->pending()->create(['work_date' => $date]);

        $this->actingAs($student)->post(route('student.logs.store'), [
            'work_date' => $date,
            'check_in' => '08:00',
            'check_out' => '12:00',
            'tasks' => 'محاولة مكررة',
        ])->assertSessionHasErrors('work_date');

        $this->assertSame(1, $placement->attendanceLogs()->count());
    }

    public function test_duplicate_work_date_is_blocked_at_the_database_level(): void
    {
        [, $placement] = $this->studentWithActivePlacement();
        $date = $this->workDate();

        AttendanceLog::factory()->for($placement)->create(['work_date' => $date]);

        $this->expectException(QueryException::class);
        AttendanceLog::factory()->for($placement)->create(['work_date' => $date]);
    }

    public function test_pending_minutes_never_count_toward_progress(): void
    {
        [$student, $placement, $field] = $this->studentWithActivePlacement();

        AttendanceLog::factory()->for($placement)->approved($field)->create([
            'work_date' => now()->subDays(2)->format('Y-m-d'),
            'check_in' => '09:00', 'check_out' => '14:00', // 5h approved
        ]);
        AttendanceLog::factory()->for($placement)->pending()->create([
            'work_date' => now()->subDays(1)->format('Y-m-d'),
            'check_in' => '09:00', 'check_out' => '17:00', // 8h pending — must NOT count
        ]);

        // The authoritative guarantee: only approved minutes accrue.
        $this->assertSame(300, $placement->approvedMinutes());
        $this->assertSame(5.0, $placement->approvedHours());
        $this->assertSame(175.0, $placement->remainingHours()); // 180 - 5, NOT 180 - 13

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
    }

    public function test_an_approved_log_is_not_editable_by_the_student(): void
    {
        [$student, $placement, $field] = $this->studentWithActivePlacement();

        $log = AttendanceLog::factory()->for($placement)->approved($field)->create([
            'work_date' => $this->workDate(),
            'tasks' => 'أصلي',
        ]);

        $this->actingAs($student)->get(route('student.logs.edit', $log))->assertForbidden();

        $this->actingAs($student)->put(route('student.logs.update', $log), [
            'work_date' => $this->workDate(),
            'check_in' => '08:00',
            'check_out' => '20:00',
            'tasks' => 'محاولة تعديل',
        ])->assertForbidden();

        $this->assertSame('أصلي', $log->fresh()->tasks);
    }
}
