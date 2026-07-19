<?php

namespace Tests\Feature;

use App\Enums\EvaluationKind;
use App\Models\AttendanceLog;
use App\Models\Evaluation;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FieldStudentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Placement}  [fieldSupervisor, placement]
     */
    private function orgWithPlacement(?User $student = null): array
    {
        $org = Organization::factory()->create();
        $field = User::factory()->fieldSupervisor($org)->create();
        $placement = Placement::factory()->create([
            'student_id' => ($student ?? User::factory()->student()->create())->id,
            'organization_id' => $org->id,
            'period_id' => TrainingPeriod::factory()->open()->create()->id,
            'field_supervisor_id' => $field->id,
        ]);

        return [$field, $placement];
    }

    /** @return array<string, int> */
    private function validScores(): array
    {
        return ['attendance' => 18, 'skills' => 26, 'behavior' => 22, 'initiative' => 20]; // = 86
    }

    public function test_students_page_lists_only_the_supervisors_own_org(): void
    {
        $mine = User::factory()->student()->create(['name' => 'طالب مؤسستي']);
        [$field] = $this->orgWithPlacement($mine);

        $this->orgWithPlacement(User::factory()->student()->create(['name' => 'طالب مؤسسة أخرى']));

        $this->actingAs($field)->get(route('field.students.index'))
            ->assertOk()
            ->assertSee('طالب مؤسستي')
            ->assertDontSee('طالب مؤسسة أخرى');
    }

    public function test_log_history_shows_for_own_org_and_403s_for_another(): void
    {
        [$field, $placement] = $this->orgWithPlacement();
        [, $foreignPlacement] = $this->orgWithPlacement();

        AttendanceLog::factory()->for($placement)->approved($field)->create();

        $this->actingAs($field)->get(route('field.students.show', $placement))->assertOk();
        $this->actingAs($field)->get(route('field.students.show', $foreignPlacement))->assertForbidden();
    }

    public function test_field_evaluation_form_renders_with_and_without_an_existing_evaluation(): void
    {
        [$field, $placement] = $this->orgWithPlacement();

        $this->actingAs($field)->get(route('field.students.evaluation.edit', $placement))->assertOk();

        $this->actingAs($field)->put(route('field.students.evaluation.update', $placement), [
            'scores' => $this->validScores(),
            'comments' => 'ملاحظة ميدانية',
        ]);

        $this->actingAs($field)->get(route('field.students.evaluation.edit', $placement))
            ->assertOk()
            ->assertSee('ملاحظة ميدانية');
    }

    public function test_submitting_field_evaluation_computes_total_server_side(): void
    {
        [$field, $placement] = $this->orgWithPlacement();

        $this->actingAs($field)
            ->put(route('field.students.evaluation.update', $placement), [
                'scores' => $this->validScores(),
                // Hostile extras that must be ignored:
                'total' => 999,
                'kind' => EvaluationKind::Academic->value,
                'evaluator_id' => 99999,
            ])
            ->assertRedirect(route('field.students.show', $placement));

        $evaluation = Evaluation::where('placement_id', $placement->id)->firstOrFail();
        $this->assertSame(EvaluationKind::Field, $evaluation->kind);
        $this->assertSame($field->id, $evaluation->evaluator_id);
        $this->assertSame('86.00', $evaluation->total);
        $this->assertNotNull($evaluation->submitted_at);
    }

    public function test_a_score_above_its_criterion_max_is_rejected(): void
    {
        [$field, $placement] = $this->orgWithPlacement();

        $this->actingAs($field)
            ->put(route('field.students.evaluation.update', $placement), [
                'scores' => ['attendance' => 25, 'skills' => 26, 'behavior' => 22, 'initiative' => 20], // attendance max is 20
            ])
            ->assertSessionHasErrors('scores.attendance');

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_cannot_evaluate_a_placement_at_another_org(): void
    {
        [$field] = $this->orgWithPlacement();
        [, $foreignPlacement] = $this->orgWithPlacement();

        $this->actingAs($field)
            ->put(route('field.students.evaluation.update', $foreignPlacement), ['scores' => $this->validScores()])
            ->assertForbidden();

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_field_evaluation_total_appears_in_the_coordinator_report(): void
    {
        [$field, $placement] = $this->orgWithPlacement();

        $this->actingAs($field)->put(route('field.students.evaluation.update', $placement), [
            'scores' => $this->validScores(),
        ]);

        $coordinator = User::factory()->coordinator()->create();

        $this->actingAs($coordinator)
            ->get(route('coordinator.reports.index', ['period' => $placement->period_id]))
            ->assertOk()
            ->assertSee('86');
    }
}
