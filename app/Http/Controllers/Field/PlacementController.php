<?php

namespace App\Http\Controllers\Field;

use App\Enums\EvaluationKind;
use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Models\Placement;
use Illuminate\View\View;

class PlacementController extends Controller
{
    /**
     * "طلابي" — every placement at the supervisor's own organisation, with
     * approved-hour totals and evaluation status. Same org-scoping rule as the
     * review queue: the org is the boundary, not the individual supervisor.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Placement::class);

        $placements = Placement::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->with(['student', 'period', 'evaluations'])
            ->withSum(
                ['attendanceLogs as approved_minutes' => fn ($q) => $q->where('status', LogStatus::Approved)],
                'minutes'
            )
            ->withCount([
                'attendanceLogs as pending_count' => fn ($q) => $q->where('status', LogStatus::Pending),
            ])
            ->get();

        return view('field.students.index', compact('placements'));
    }

    /** One student's full log history — including approved entries, which can be reverted here. */
    public function show(Placement $placement): View
    {
        $this->authorize('view', $placement);

        $placement->load(['student', 'organization', 'period']);

        $logs = $placement->attendanceLogs()
            ->orderByDesc('work_date')
            ->get();

        $evaluations = $placement->evaluations()->with('evaluator')->get()
            ->keyBy(fn ($e) => $e->kind->value);

        return view('field.students.show', [
            'placement' => $placement,
            'logs' => $logs,
            'fieldEvaluation' => $evaluations->get(EvaluationKind::Field->value),
        ]);
    }
}
