<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Placement, 2: TrainingPeriod}
     */
    private function seededReport(): array
    {
        $coordinator = User::factory()->coordinator()->create();
        $org = Organization::factory()->create();
        $period = TrainingPeriod::factory()->open()->create(['required_hours' => 180]);
        $field = User::factory()->fieldSupervisor($org)->create();
        $student = User::factory()->student()->create(['name' => 'طالب التقرير']);

        $placement = Placement::factory()->create([
            'student_id' => $student->id,
            'organization_id' => $org->id,
            'period_id' => $period->id,
            'field_supervisor_id' => $field->id,
        ]);

        // 2 approved (5h + 6h = 11h), 1 pending (8h — must not count anywhere).
        AttendanceLog::factory()->for($placement)->approved($field)->create([
            'work_date' => now()->subDays(4)->format('Y-m-d'),
            'check_in' => '09:00', 'check_out' => '14:00',
        ]);
        AttendanceLog::factory()->for($placement)->approved($field)->create([
            'work_date' => now()->subDays(3)->format('Y-m-d'),
            'check_in' => '08:00', 'check_out' => '14:00',
        ]);
        AttendanceLog::factory()->for($placement)->pending()->create([
            'work_date' => now()->subDays(2)->format('Y-m-d'),
            'check_in' => '08:00', 'check_out' => '16:00',
        ]);

        return [$coordinator, $placement, $period];
    }

    public function test_reports_index_shows_approved_hours_only(): void
    {
        [$coordinator, $placement, $period] = $this->seededReport();

        $this->actingAs($coordinator)
            ->get(route('coordinator.reports.index'))
            ->assertOk()
            ->assertSee('طالب التقرير')
            ->assertSee('11 / 180'); // approved only — 19 would mean pending leaked in
    }

    public function test_per_student_report_renders_and_separates_unapproved_logs(): void
    {
        [$coordinator, $placement] = $this->seededReport();

        $this->actingAs($coordinator)
            ->get(route('coordinator.reports.show', $placement))
            ->assertOk()
            ->assertSee('السجلات المعتمدة (2)')
            ->assertSee('سجلات غير معتمدة — لا تُحتسب (1)');
    }

    public function test_csv_export_contains_approved_hours_and_a_bom(): void
    {
        [$coordinator, , $period] = $this->seededReport();

        $response = $this->actingAs($coordinator)
            ->get(route('coordinator.reports.export', ['period' => $period->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv); // Excel needs the BOM for Arabic
        $this->assertStringContainsString('طالب التقرير', $csv);
        $this->assertStringContainsString('11', $csv);  // approved hours
        $this->assertStringContainsString(',1,', $csv); // one pending log, counted as a count only
    }

    public function test_reports_are_coordinator_only(): void
    {
        [, $placement, $period] = $this->seededReport();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('coordinator.reports.index'))->assertForbidden();
        $this->actingAs($student)->get(route('coordinator.reports.show', $placement))->assertForbidden();
        $this->actingAs($student)->get(route('coordinator.reports.export', ['period' => $period->id]))->assertForbidden();
    }
}
