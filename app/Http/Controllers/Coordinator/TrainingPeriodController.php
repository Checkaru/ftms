<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\TrainingPeriodRequest;
use App\Models\TrainingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TrainingPeriodController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TrainingPeriod::class);

        $periods = TrainingPeriod::withCount('placements')
            ->orderByDesc('starts_on')
            ->paginate(15);

        return view('coordinator.periods.index', compact('periods'));
    }

    public function create(): View
    {
        $this->authorize('create', TrainingPeriod::class);

        return view('coordinator.periods.create', ['period' => new TrainingPeriod()]);
    }

    public function store(TrainingPeriodRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingPeriod::class);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $this->closeOthersIfOpening($data);
            TrainingPeriod::create($data);
        });

        return redirect()->route('coordinator.periods.index')
            ->with('success', 'تمت إضافة الفترة.');
    }

    public function edit(TrainingPeriod $period): View
    {
        $this->authorize('update', $period);

        return view('coordinator.periods.edit', compact('period'));
    }

    public function update(TrainingPeriodRequest $request, TrainingPeriod $period): RedirectResponse
    {
        $this->authorize('update', $period);

        $data = $request->validated();

        DB::transaction(function () use ($data, $period) {
            $this->closeOthersIfOpening($data, $period->id);
            $period->update($data);
        });

        return redirect()->route('coordinator.periods.index')
            ->with('success', 'تم تحديث الفترة.');
    }

    public function destroy(TrainingPeriod $period): RedirectResponse
    {
        $this->authorize('delete', $period);

        // Deleting a period cascades its placements and logs — block if it has any.
        if ($period->placements()->exists()) {
            return redirect()->route('coordinator.periods.index')
                ->with('error', 'لا يمكن حذف فترة مرتبطة بتنسيبات.');
        }

        $period->delete();

        return redirect()->route('coordinator.periods.index')
            ->with('success', 'تم حذف الفترة.');
    }

    /**
     * Only one period is open at a time: if this one is being opened, close the rest.
     *
     * @param  array<string, mixed>  $data
     */
    private function closeOthersIfOpening(array $data, ?int $exceptId = null): void
    {
        if (! empty($data['is_open'])) {
            TrainingPeriod::where('is_open', true)
                ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
                ->update(['is_open' => false]);
        }
    }
}
