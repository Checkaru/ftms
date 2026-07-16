<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The student's home. Answers one question above the fold: how many hours
     * are left? Only APPROVED hours are ever shown as progress.
     */
    public function __invoke(): View
    {
        $user = auth()->user();
        $placement = $user->activePlacement();

        $recentLogs = $placement
            ? $placement->attendanceLogs()->orderByDesc('work_date')->limit(8)->get()
            : collect();

        // A pending COUNT is fine to surface; pending MINUTES never are.
        $pendingCount = $placement
            ? $placement->attendanceLogs()->where('status', \App\Enums\LogStatus::Pending)->count()
            : 0;

        return view('student.dashboard', compact('placement', 'recentLogs', 'pendingCount'));
    }
}
