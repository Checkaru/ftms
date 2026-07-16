<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\PlacementRequest;
use App\Models\Organization;
use App\Models\Placement;
use App\Models\TrainingPeriod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Placement::class);

        // Eager-load so the table doesn't N+1 across student / org / period.
        $placements = Placement::with(['student', 'organization', 'period', 'fieldSupervisor', 'academicSupervisor'])
            ->latest()
            ->paginate(15);

        return view('coordinator.placements.index', compact('placements'));
    }

    public function create(): View
    {
        $this->authorize('create', Placement::class);

        return view('coordinator.placements.create', [
            'placement' => new Placement(['status' => \App\Enums\PlacementStatus::Active]),
            ...$this->formOptions(),
        ]);
    }

    public function store(PlacementRequest $request): RedirectResponse
    {
        $this->authorize('create', Placement::class);

        Placement::create($request->validated());

        return redirect()->route('coordinator.placements.index')
            ->with('success', 'تمت إضافة التنسيب.');
    }

    public function edit(Placement $placement): View
    {
        $this->authorize('update', $placement);

        return view('coordinator.placements.edit', [
            'placement' => $placement,
            ...$this->formOptions(),
        ]);
    }

    public function update(PlacementRequest $request, Placement $placement): RedirectResponse
    {
        $this->authorize('update', $placement);

        $placement->update($request->validated());

        return redirect()->route('coordinator.placements.index')
            ->with('success', 'تم تحديث التنسيب.');
    }

    public function destroy(Placement $placement): RedirectResponse
    {
        $this->authorize('delete', $placement);

        $placement->delete();

        return redirect()->route('coordinator.placements.index')
            ->with('success', 'تم حذف التنسيب.');
    }

    /**
     * Dropdown data for the create/edit form.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function formOptions(): array
    {
        return [
            'students' => User::where('role', UserRole::Student)->orderBy('name')->get(),
            'organizations' => Organization::orderBy('name')->get(),
            'periods' => TrainingPeriod::orderByDesc('starts_on')->get(),
            'fieldSupervisors' => User::where('role', UserRole::FieldSupervisor)->orderBy('name')->get(),
            'academicSupervisors' => User::where('role', UserRole::AcademicSupervisor)->orderBy('name')->get(),
        ];
    }
}
