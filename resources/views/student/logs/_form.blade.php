@csrf

<div class="mb-5 text-sm text-gray-500 bg-gray-50 rounded-md px-4 py-3">
    المؤسسة: <strong class="text-gray-700">{{ $placement->organization->name }}</strong>
    · الفترة: <strong class="text-gray-700">{{ $placement->period->name }}</strong>
    ({{ $placement->period->starts_on->format('Y/m/d') }} – {{ $placement->period->ends_on->format('Y/m/d') }})
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <div>
        <x-input-label for="work_date" value="تاريخ اليوم" />
        <x-text-input id="work_date" name="work_date" type="date" class="mt-1 block w-full"
                      :value="old('work_date', optional($log->work_date)->format('Y-m-d'))" required autofocus />
        <x-input-error :messages="$errors->get('work_date')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="check_in" value="وقت الحضور" />
        <x-text-input id="check_in" name="check_in" type="time" class="mt-1 block w-full"
                      :value="old('check_in', $log->check_in ? substr($log->check_in, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('check_in')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="check_out" value="وقت الانصراف" />
        <x-text-input id="check_out" name="check_out" type="time" class="mt-1 block w-full"
                      :value="old('check_out', $log->check_out ? substr($log->check_out, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('check_out')" class="mt-1" />
    </div>

    <div class="md:col-span-3">
        <x-input-label for="tasks" value="المهام المنجزة" />
        <textarea id="tasks" name="tasks" rows="4" required maxlength="1000"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('tasks', $log->tasks) }}</textarea>
        <x-input-error :messages="$errors->get('tasks')" class="mt-1" />
    </div>
</div>
