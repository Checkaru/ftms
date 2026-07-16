<?php

namespace App\Http\Controllers\Academic;

use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Models\Placement;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The academic supervisor's assigned students, with approved-hour totals. */
    public function __invoke(): View
    {
        $placements = Placement::where('academic_supervisor_id', auth()->id())
            ->with(['student', 'organization', 'period', 'evaluations'])
            ->withSum(
                ['attendanceLogs as approved_minutes' => fn ($q) => $q->where('status', LogStatus::Approved)],
                'minutes'
            )
            ->get();

        return view('academic.dashboard', compact('placements'));
    }
}
