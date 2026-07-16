<?php

namespace Tests\Feature;

use App\Enums\EvaluationKind;
use App\Models\Evaluation;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicEvaluationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Placement}  [academicSupervisor, placement]
     */
    private function assignedPlacement(?User $student = null): array
    {
        $academic = User::factory()->academicSupervisor()->create();
        $placement = Placement::factory()->create([
            'student_id' => ($student ?? User::factory()->student()->create())->id,
            'organization_id' => Organization::factory()->create()->id,
            'period_id' => TrainingPeriod::factory()->open()->create()->id,
            'academic_supervisor_id' => $academic->id,
        ]);

        return [$academic, $placement];
    }

    /** @return array<string, int> */
    private function validScores(): array
    {
        return ['report' => 35, 'objectives' => 28, 'presentation' => 27]; // = 90
    }

    public function test_dashboard_lists_only_assigned_students(): void
    {
        $mine = User::factory()->student()->create(['name' => 'طالبي المُسند']);
        [$academic] = $this->assignedPlacement($mine);

        // Another academic supervisor's student:
        $this->assignedPlacement(User::factory()->student()->create(['name' => 'طالب غيري']));

        $this->actingAs($academic)->get(route('academic.dashboard'))
            ->assertOk()
            ->assertSee('طالبي المُسند')
            ->assertDontSee('طالب غيري');
    }

    public function test_can_view_assigned_placement_but_not_an_unassigned_one(): void
    {
        [$academic, $placement] = $this->assignedPlacement();
        [, $otherPlacement] = $this->assignedPlacement();

        $this->actingAs($academic)->get(route('academic.placements.show', $placement))->assertOk();
        $this->actingAs($academic)->get(route('academic.placements.show', $otherPlacement))->assertForbidden();
    }

    public function test_evaluation_form_renders_with_and_without_an_existing_evaluation(): void
    {
        [$academic, $placement] = $this->assignedPlacement();

        // No evaluation yet — the form must still render (regression: null $evaluation).
        $this->actingAs($academic)->get(route('academic.evaluation.edit', $placement))->assertOk();

        // After one exists, it must pre-fill.
        $this->actingAs($academic)->put(route('academic.evaluation.update', $placement), [
            'scores' => $this->validScores(),
            'comments' => 'ملاحظة سابقة',
        ]);

        $this->actingAs($academic)->get(route('academic.evaluation.edit', $placement))
            ->assertOk()
            ->assertSee('ملاحظة سابقة');
    }

    public function test_submitting_evaluation_computes_total_server_side_and_ignores_injected_fields(): void
    {
        [$academic, $placement] = $this->assignedPlacement();

        $this->actingAs($academic)
            ->put(route('academic.evaluation.update', $placement), [
                'scores' => $this->validScores(),
                'comments' => 'أداء جيد',
                // Hostile extras that must be ignored:
                'total' => 999,
                'kind' => EvaluationKind::Field->value,
                'evaluator_id' => 99999,
                'submitted_at' => '2000-01-01',
            ])
            ->assertRedirect(route('academic.placements.show', $placement));

        $evaluation = Evaluation::where('placement_id', $placement->id)->firstOrFail();
        $this->assertSame(EvaluationKind::Academic, $evaluation->kind);
        $this->assertSame($academic->id, $evaluation->evaluator_id);
        $this->assertSame('90.00', $evaluation->total); // server-summed, not 999
        $this->assertNotNull($evaluation->submitted_at);
    }

    public function test_a_score_above_its_max_is_rejected(): void
    {
        [$academic, $placement] = $this->assignedPlacement();

        $this->actingAs($academic)
            ->put(route('academic.evaluation.update', $placement), [
                'scores' => ['report' => 50, 'objectives' => 28, 'presentation' => 27], // report max is 40
            ])
            ->assertSessionHasErrors('scores.report');

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_cannot_evaluate_an_unassigned_placement(): void
    {
        [$academic] = $this->assignedPlacement();
        [, $otherPlacement] = $this->assignedPlacement();

        $this->actingAs($academic)
            ->put(route('academic.evaluation.update', $otherPlacement), ['scores' => $this->validScores()])
            ->assertForbidden();

        $this->assertDatabaseCount('evaluations', 0);
    }

    public function test_resubmitting_updates_the_same_evaluation(): void
    {
        [$academic, $placement] = $this->assignedPlacement();

        $this->actingAs($academic)->put(route('academic.evaluation.update', $placement), [
            'scores' => $this->validScores(),
        ])->assertRedirect();

        $this->actingAs($academic)->put(route('academic.evaluation.update', $placement), [
            'scores' => ['report' => 40, 'objectives' => 30, 'presentation' => 30], // = 100
        ])->assertRedirect();

        $this->assertDatabaseCount('evaluations', 1);
        $this->assertSame('100.00', Evaluation::first()->total);
    }
}
