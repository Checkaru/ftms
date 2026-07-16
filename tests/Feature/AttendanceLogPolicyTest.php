<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLogPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** Build a pending log for a placement at $org supervised by $fieldSup. */
    private function pendingLogAt(Organization $org, User $fieldSup): AttendanceLog
    {
        $placement = Placement::factory()->create([
            'student_id' => User::factory()->student()->create()->id,
            'organization_id' => $org->id,
            'period_id' => TrainingPeriod::factory()->open()->create()->id,
            'field_supervisor_id' => $fieldSup->id,
        ]);

        return AttendanceLog::factory()->for($placement)->pending()->create();
    }

    public function test_field_supervisor_can_review_a_log_at_their_own_org(): void
    {
        $orgA = Organization::factory()->create();
        $fieldA = User::factory()->fieldSupervisor($orgA)->create();

        $this->assertTrue($fieldA->can('review', $this->pendingLogAt($orgA, $fieldA)));
    }

    /** The headline rule: a supervisor must not touch another org's students. */
    public function test_field_supervisor_cannot_review_a_log_from_another_org(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $fieldA = User::factory()->fieldSupervisor($orgA)->create();
        $fieldB = User::factory()->fieldSupervisor($orgB)->create();

        $log = $this->pendingLogAt($orgA, $fieldA);

        $this->assertFalse($fieldB->can('review', $log));
    }

    public function test_coordinator_may_view_but_never_review(): void
    {
        $orgA = Organization::factory()->create();
        $fieldA = User::factory()->fieldSupervisor($orgA)->create();
        $coordinator = User::factory()->coordinator()->create();

        $log = $this->pendingLogAt($orgA, $fieldA);

        $this->assertTrue($coordinator->can('view', $log));
        $this->assertFalse($coordinator->can('review', $log));
    }

    public function test_student_can_edit_a_pending_log_but_not_an_approved_one(): void
    {
        $orgA = Organization::factory()->create();
        $fieldA = User::factory()->fieldSupervisor($orgA)->create();
        $student = User::factory()->student()->create();

        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'organization_id' => $orgA->id,
            'period_id' => TrainingPeriod::factory()->open()->create()->id,
            'field_supervisor_id' => $fieldA->id,
        ]);

        $pending = AttendanceLog::factory()->for($placement)->pending()->create();
        $approved = AttendanceLog::factory()->for($placement)->approved($fieldA)->create();

        $this->assertTrue($student->can('update', $pending));
        $this->assertFalse($student->can('update', $approved));
    }
}
