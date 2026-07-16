<?php

namespace App\Http\Controllers\Academic;

use App\Enums\EvaluationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicEvaluationRequest;
use App\Models\Evaluation;
use App\Models\Placement;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    /** The academic evaluation form (create or edit — one per placement). */
    public function edit(Placement $placement): View
    {
        $this->authorize('submitAcademicEvaluation', $placement);

        $evaluation = $placement->evaluations()
            ->where('kind', EvaluationKind::Academic)
            ->first();

        return view('academic.evaluation', [
            'placement' => $placement->load(['student', 'period']),
            'evaluation' => $evaluation,
            'rubric' => config('training.rubrics.'.EvaluationKind::Academic->value, []),
        ]);
    }

    public function update(AcademicEvaluationRequest $request, Placement $placement): RedirectResponse
    {
        // Build scores ONLY from known rubric keys — never trust the client for
        // the set of criteria or the total; the total is summed server-side.
        $scores = [];
        foreach ($request->rubric() as $key => $meta) {
            $scores[$key] = (int) $request->input("scores.$key");
        }

        $evaluation = $placement->evaluations()
            ->where('kind', EvaluationKind::Academic)
            ->first() ?? new Evaluation();

        // Everything below is set explicitly (kind/evaluator/submitted are not fillable).
        $evaluation->placement_id = $placement->id;
        $evaluation->kind = EvaluationKind::Academic;
        $evaluation->evaluator_id = auth()->id();
        $evaluation->scores = $scores;
        $evaluation->total = array_sum($scores);
        $evaluation->comments = $request->input('comments');
        $evaluation->submitted_at = now();
        $evaluation->save();

        return redirect()->route('academic.placements.show', $placement)
            ->with('success', 'تم حفظ التقييم الأكاديمي والدرجة.');
    }
}
