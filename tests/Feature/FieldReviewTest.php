<?php

namespace Tests\Feature;

use App\Enums\LogStatus;
use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Placement}  [fieldSupervisor, placement]
     */
    private function orgWithPlacement(?User $student = null): array
    {
        $org = Organization::factory()->create();
        $period = TrainingPeriod::factory()->open()->create();
        $field = User::factory()->fieldSupervisor($org)->create();
        $placement = Placement::factory()->create([
            'student_id' => ($student ?? User::factory()->student()->create())->id,
            'organization_id' => $org->id,
            'period_id' => $period->id,
            'field_supervisor_id' => $field->id,
        ]);

        return [$field, $placement];
    }

    public function test_supervisor_can_approve_a_log_at_their_org(): void
    {
        [$field, $placement] = $this->orgWithPlacement();
        $log = AttendanceLog::factory()->for($placement)->pending()->create();

        $this->actingAs($field)
            ->post(route('field.logs.approve', $log))
            ->assertRedirect();

        $log->refresh();
        $this->assertSame(LogStatus::Approved, $log->status);
        $this->assertSame($field->id, $log->reviewed_by);
        $this->assertNotNull($log->reviewed_at);
    }

    public function test_supervisor_cannot_approve_a_log_from_another_org(): void
    {
        [, $placementA] = $this->orgWithPlacement();
        [$fieldB] = $this->orgWithPlacement();
        $log = AttendanceLog::factory()->for($placementA)->pending()->create();

        $this->actingAs($fieldB)
            ->post(route('field.logs.approve', $log))
            ->assertForbidden();

        $this->assertSame(LogStatus::Pending, $log->fresh()->status);
    }

    public function test_reject_requires_a_reason_and_records_it(): void
    {
        [$field, $placement] = $this->orgWithPlacement();
        $log = AttendanceLog::factory()->for($placement)->pending()->create();

        // No reason → validation error, still pending.
        $this->actingAs($field)
            ->post(route('field.logs.reject', $log), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame(LogStatus::Pending, $log->fresh()->status);

        // With reason → rejected and reason stored.
        $this->actingAs($field)
            ->post(route('field.logs.reject', $log), ['reason' => 'التوقيت غير مطابق'])
            ->assertRedirect();

        $log->refresh();
        $this->assertSame(LogStatus::Rejected, $log->status);
        $this->assertSame('التوقيت غير مطابق', $log->reject_reason);
    }

    public function test_bulk_approve_approves_all_selected_logs(): void
    {
        [$field, $placement] = $this->orgWithPlacement();
        $logs = AttendanceLog::factory()->count(3)->for($placement)->pending()
            ->sequence(
                ['work_date' => now()->subDays(5)->format('Y-m-d')],
                ['work_date' => now()->subDays(4)->format('Y-m-d')],
                ['work_date' => now()->subDays(3)->format('Y-m-d')],
            )->create();

        $this->actingAs($field)
            ->post(route('field.logs.bulkApprove'), ['ids' => $logs->pluck('id')->all()])
            ->assertRedirect();

        $this->assertSame(0, AttendanceLog::where('status', LogStatus::Pending)->count());
        $this->assertSame(3, AttendanceLog::where('status', LogStatus::Approved)->count());
    }

    public function test_bulk_approve_is_rejected_if_any_log_is_foreign(): void
    {
        [$field, $placementA] = $this->orgWithPlacement();
        [, $placementB] = $this->orgWithPlacement();
        $mine = AttendanceLog::factory()->for($placementA)->pending()->create();
        $foreign = AttendanceLog::factory()->for($placementB)->pending()->create();

        $this->actingAs($field)
            ->post(route('field.logs.bulkApprove'), ['ids' => [$mine->id, $foreign->id]])
            ->assertForbidden();

        // Nothing approved: authorisation is checked before any write.
        $this->assertSame(2, AttendanceLog::where('status', LogStatus::Pending)->count());
    }

    public function test_queue_shows_only_the_supervisors_own_org(): void
    {
        $mine = User::factory()->student()->create(['name' => 'طالبي الخاص']);
        [$field, $placementA] = $this->orgWithPlacement($mine);
        AttendanceLog::factory()->for($placementA)->pending()->create();

        $foreignStudent = User::factory()->student()->create(['name' => 'طالب مؤسسة أخرى']);
        [, $placementB] = $this->orgWithPlacement($foreignStudent);
        AttendanceLog::factory()->for($placementB)->pending()->create();

        $this->actingAs($field)->get(route('field.dashboard'))
            ->assertOk()
            ->assertSee('طالبي الخاص')
            ->assertDontSee('طالب مؤسسة أخرى');
    }

    public function test_supervisor_can_revert_an_approved_log(): void
    {
        [$field, $placement] = $this->orgWithPlacement();
        $log = AttendanceLog::factory()->for($placement)->approved($field)->create();

        $this->actingAs($field)
            ->post(route('field.logs.revert', $log))
            ->assertRedirect();

        $this->assertSame(LogStatus::Pending, $log->fresh()->status);
        $this->assertNull($log->fresh()->reviewed_by);
    }
}
