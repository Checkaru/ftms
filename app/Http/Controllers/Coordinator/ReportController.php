<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\EvaluationKind;
use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Per-student hour report, filterable by period (defaults to the open one). */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Placement::class);

        $periods = TrainingPeriod::orderByDesc('starts_on')->get();
        $period = $this->resolvePeriod($request, $periods);

        $placements = $period
            ? $this->reportQuery($period->id)->get()
            : collect();

        return view('coordinator.reports.index', compact('periods', 'period', 'placements'));
    }

    /** One student's full, printable hour report. */
    public function show(Placement $placement): View
    {
        $this->authorize('view', $placement);

        $placement->load(['student', 'organization', 'period', 'fieldSupervisor', 'academicSupervisor']);

        $logs = $placement->attendanceLogs()
            ->orderBy('work_date')
            ->get();

        $evaluations = $placement->evaluations()->with('evaluator')->get()
            ->keyBy(fn ($e) => $e->kind->value);

        return view('coordinator.reports.show', [
            'placement' => $placement,
            'logs' => $logs,
            'fieldEvaluation' => $evaluations->get(EvaluationKind::Field->value),
            'academicEvaluation' => $evaluations->get(EvaluationKind::Academic->value),
        ]);
    }

    /** CSV of the same report — UTF-8 BOM so Arabic opens correctly in Excel. */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Placement::class);

        $periods = TrainingPeriod::orderByDesc('starts_on')->get();
        $period = $this->resolvePeriod($request, $periods);

        abort_if($period === null, 404, 'لا توجد فترة للتصدير.');

        $placements = $this->reportQuery($period->id)->get();
        $filename = 'training-report-'.$period->id.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($placements, $period) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF"); // BOM — Excel misreads Arabic without it

            fputcsv($out, [
                'الرقم الجامعي', 'الطالب', 'المؤسسة', 'الفترة',
                'الساعات المعتمدة', 'الساعات المطلوبة', 'نسبة الإنجاز %',
                'سجلات معلقة', 'سجلات مرفوضة',
                'التقييم الميداني', 'التقييم الأكاديمي', 'حالة التنسيب',
            ]);

            foreach ($placements as $placement) {
                $approvedHours = round(($placement->approved_minutes ?? 0) / 60, 1);
                $required = $period->required_hours;

                $field = $placement->evaluations->firstWhere('kind', EvaluationKind::Field);
                $academic = $placement->evaluations->firstWhere('kind', EvaluationKind::Academic);

                fputcsv($out, [
                    $placement->student->student_number,
                    $placement->student->name,
                    $placement->organization->name,
                    $period->name,
                    $approvedHours,
                    $required,
                    $required > 0 ? min(100, round($approvedHours / $required * 100)) : 0,
                    $placement->pending_count,
                    $placement->rejected_count,
                    $field?->total ?? '',
                    $academic?->total ?? '',
                    $placement->status->label(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** The report dataset: one row per placement in the period, no N+1. */
    private function reportQuery(int $periodId): Builder
    {
        return Placement::query()
            ->where('period_id', $periodId)
            ->with(['student', 'organization', 'evaluations'])
            ->withSum(
                ['attendanceLogs as approved_minutes' => fn ($q) => $q->where('status', LogStatus::Approved)],
                'minutes'
            )
            ->withCount([
                'attendanceLogs as pending_count' => fn ($q) => $q->where('status', LogStatus::Pending),
                'attendanceLogs as rejected_count' => fn ($q) => $q->where('status', LogStatus::Rejected),
            ]);
    }

    private function resolvePeriod(Request $request, $periods): ?TrainingPeriod
    {
        if ($request->filled('period')) {
            return $periods->firstWhere('id', (int) $request->query('period'));
        }

        return $periods->firstWhere('is_open', true) ?? $periods->first();
    }
}
