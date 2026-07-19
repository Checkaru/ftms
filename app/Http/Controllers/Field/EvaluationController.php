<?php

namespace App\Http\Controllers\Field;

use App\Enums\EvaluationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Field\FieldEvaluationRequest;
use App\Models\Evaluation;
use App\Models\Placement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    /** The field evaluation form (create or edit — one per placement). */
    public function edit(Placement $placement): View
    {
        $this->authorize('submitFieldEvaluation', $placement);

        $evaluation = $placement->evaluations()
            ->where('kind', EvaluationKind::Field)
            ->first();

        return view('field.evaluation', [
            'placement' => $placement->load(['student', 'period']),
            'evaluation' => $evaluation,
            'rubric' => config('training.rubrics.'.EvaluationKind::Field->value, []),
        ]);
    }

    public function update(FieldEvaluationRequest $request, Placement $placement): RedirectResponse
    {
        // Scores are built only from known rubric keys; the total is summed
        // server-side. kind/evaluator/submitted_at are never client-supplied.
        $scores = [];
        foreach ($request->rubric() as $key => $meta) {
            $scores[$key] = (int) $request->input("scores.$key");
        }

        $evaluation = $placement->evaluations()
            ->where('kind', EvaluationKind::Field)
            ->first() ?? new Evaluation();

        $evaluation->placement_id = $placement->id;
        $evaluation->kind = EvaluationKind::Field;
        $evaluation->evaluator_id = auth()->id();
        $evaluation->scores = $scores;
        $evaluation->total = array_sum($scores);
        $evaluation->comments = $request->input('comments');
        $evaluation->submitted_at = now();
        $evaluation->save();

        return redirect()->route('field.students.show', $placement)
            ->with('success', 'تم حفظ التقييم الميداني.');
    }
}
