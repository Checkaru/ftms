<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\LogStatus;
use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('coordinator.dashboard', [
            'counts' => [
                'organizations' => Organization::count(),
                'periods' => TrainingPeriod::count(),
                'placements' => Placement::count(),
                'users' => User::count(),
                'pendingLogs' => AttendanceLog::where('status', LogStatus::Pending)->count(),
            ],
            'openPeriod' => TrainingPeriod::where('is_open', true)->first(),
        ]);
    }
}
