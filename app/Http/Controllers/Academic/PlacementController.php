<?php

namespace App\Http\Controllers\Academic;

use App\Enums\EvaluationKind;
use App\Http\Controllers\Controller;
use App\Models\Placement;
use Illuminate\View\View;

class PlacementController extends Controller
{
    /** Read-only view of one assigned student: full logs, hours, evaluations. */
    public function show(Placement $placement): View
    {
        $this->authorize('view', $placement);

        $placement->load(['student', 'organization', 'period']);

        $logs = $placement->attendanceLogs()
            ->with('reviewer')
            ->orderByDesc('work_date')
            ->get();

        $evaluations = $placement->evaluations()->with('evaluator')->get()->keyBy(fn ($e) => $e->kind->value);

        return view('academic.placements.show', [
            'placement' => $placement,
            'logs' => $logs,
            'fieldEvaluation' => $evaluations->get(EvaluationKind::Field->value),
            'academicEvaluation' => $evaluations->get(EvaluationKind::Academic->value),
        ]);
    }
}
