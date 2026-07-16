<?php

namespace App\Http\Controllers\Student;

use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttendanceLogRequest;
use App\Models\AttendanceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceLogController extends Controller
{
    public function index(): View
    {
        $placement = auth()->user()->activePlacement();

        $logs = $placement
            ? $placement->attendanceLogs()->orderByDesc('work_date')->paginate(20)
            : null;

        return view('student.logs.index', compact('placement', 'logs'));
    }

    public function create(): View|RedirectResponse
    {
        $placement = auth()->user()->activePlacement();

        if ($placement === null) {
            return redirect()->route('student.dashboard')
                ->with('error', 'لا يوجد تنسيب فعّال في فترة مفتوحة لتسجيل الحضور.');
        }

        return view('student.logs.create', [
            'placement' => $placement,
            'log' => new AttendanceLog(),
        ]);
    }

    public function store(AttendanceLogRequest $request): RedirectResponse
    {
        $placement = $request->targetPlacement();

        // Only the four whitelisted fields come from validated(); status/minutes
        // are never client-supplied. status is set here, minutes by the model.
        $log = new AttendanceLog($request->validated());
        $log->placement()->associate($placement);
        $log->status = LogStatus::Pending;
        $log->save();

        return redirect()->route('student.dashboard')
            ->with('success', 'تم تسجيل الحضور، بانتظار اعتماد المشرف.');
    }

    public function edit(AttendanceLog $log): View
    {
        $this->authorize('update', $log);

        return view('student.logs.edit', [
            'placement' => $log->placement,
            'log' => $log,
        ]);
    }

    public function update(AttendanceLogRequest $request, AttendanceLog $log): RedirectResponse
    {
        // Authorisation handled in the FormRequest (own log, not approved).
        $log->fill($request->validated());

        // Editing a (pending or rejected) entry resubmits it for review.
        $log->status = LogStatus::Pending;
        $log->reviewed_by = null;
        $log->reviewed_at = null;
        $log->reject_reason = null;
        $log->save();

        return redirect()->route('student.logs.index')
            ->with('success', 'تم تحديث السجل وإعادته للمراجعة.');
    }

    public function destroy(AttendanceLog $log): RedirectResponse
    {
        $this->authorize('delete', $log);

        $log->delete();

        return redirect()->route('student.logs.index')
            ->with('success', 'تم حذف السجل.');
    }
}
