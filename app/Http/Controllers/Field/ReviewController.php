<?php

namespace App\Http\Controllers\Field;

use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Field\BulkApproveRequest;
use App\Http\Requests\Field\RejectAttendanceLogRequest;
use App\Models\AttendanceLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * The field supervisor's home IS the pending queue — scoped to their own
     * organisation only, eager-loaded to avoid an N+1 that grows every week.
     */
    public function index(): View
    {
        $user = auth()->user();

        $logs = AttendanceLog::query()
            ->where('status', LogStatus::Pending)
            ->whereHas('placement', fn ($q) => $q->where('organization_id', $user->organization_id))
            ->with(['placement.student'])
            ->orderBy('work_date')
            ->get();

        return view('field.dashboard', ['logs' => $logs]);
    }

    public function approve(AttendanceLog $log): RedirectResponse
    {
        $this->authorize('review', $log);

        $log->approveBy(auth()->user());

        return back()->with('success', 'تم اعتماد السجل.');
    }

    public function reject(RejectAttendanceLogRequest $request, AttendanceLog $log): RedirectResponse
    {
        // Authorisation is enforced in the request.
        $log->rejectBy(auth()->user(), $request->validated()['reason']);

        return back()->with('success', 'تم رفض السجل مع إبلاغ الطالب بالسبب.');
    }

    /** Send an approved entry back for correction. */
    public function revert(AttendanceLog $log): RedirectResponse
    {
        $this->authorize('review', $log);

        if (! $log->isApproved()) {
            return back()->with('error', 'يمكن التراجع عن السجلات المعتمدة فقط.');
        }

        $log->revertBy(auth()->user());

        return back()->with('success', 'تمت إعادة السجل إلى قيد المراجعة.');
    }

    public function bulkApprove(BulkApproveRequest $request): RedirectResponse
    {
        $logs = AttendanceLog::whereIn('id', $request->validated()['ids'])
            ->with('placement')
            ->get();

        // Reject the whole batch if any log is not this supervisor's to review.
        foreach ($logs as $log) {
            $this->authorize('review', $log);
        }

        $approved = 0;
        DB::transaction(function () use ($logs, &$approved) {
            $reviewer = auth()->user();
            foreach ($logs as $log) {
                if ($log->isPending()) {
                    $log->approveBy($reviewer);
                    $approved++;
                }
            }
        });

        return back()->with('success', "تم اعتماد {$approved} سجل.");
    }
}
