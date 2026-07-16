@csrf

@php
    $selectClass = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <x-input-label for="student_id" value="الطالب" />
        <select id="student_id" name="student_id" class="{{ $selectClass }}" required>
            <option value="">— اختر الطالب —</option>
            @foreach ($students as $student)
                <option value="{{ $student->id }}"
                    @selected((int) old('student_id', $placement->student_id) === $student->id)>
                    {{ $student->name }}@if ($student->student_number) (#{{ $student->student_number }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('student_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="period_id" value="فترة التدريب" />
        <select id="period_id" name="period_id" class="{{ $selectClass }}" required>
            <option value="">— اختر الفترة —</option>
            @foreach ($periods as $period)
                <option value="{{ $period->id }}"
                    @selected((int) old('period_id', $placement->period_id) === $period->id)>
                    {{ $period->name }}@if ($period->is_open) — مفتوحة @endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('period_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="organization_id" value="المؤسسة" />
        <select id="organization_id" name="organization_id" class="{{ $selectClass }}" required>
            <option value="">— اختر المؤسسة —</option>
            @foreach ($organizations as $organization)
                <option value="{{ $organization->id }}"
                    @selected((int) old('organization_id', $placement->organization_id) === $organization->id)>
                    {{ $organization->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('organization_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="status" value="الحالة" />
        <select id="status" name="status" class="{{ $selectClass }}" required>
            @foreach (\App\Enums\PlacementStatus::cases() as $case)
                <option value="{{ $case->value }}"
                    @selected(old('status', $placement->status?->value ?? \App\Enums\PlacementStatus::Active->value) === $case->value)>
                    {{ $case->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="field_supervisor_id" value="المشرف الميداني" />
        <select id="field_supervisor_id" name="field_supervisor_id" class="{{ $selectClass }}">
            <option value="">— بدون —</option>
            @foreach ($fieldSupervisors as $supervisor)
                <option value="{{ $supervisor->id }}"
                    @selected((int) old('field_supervisor_id', $placement->field_supervisor_id) === $supervisor->id)>
                    {{ $supervisor->name }}@if ($supervisor->organization) — {{ $supervisor->organization->name }}@endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-400">يجب أن يتبع نفس المؤسسة المختارة.</p>
        <x-input-error :messages="$errors->get('field_supervisor_id')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="academic_supervisor_id" value="المشرف الأكاديمي" />
        <select id="academic_supervisor_id" name="academic_supervisor_id" class="{{ $selectClass }}">
            <option value="">— بدون —</option>
            @foreach ($academicSupervisors as $supervisor)
                <option value="{{ $supervisor->id }}"
                    @selected((int) old('academic_supervisor_id', $placement->academic_supervisor_id) === $supervisor->id)>
                    {{ $supervisor->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('academic_supervisor_id')" class="mt-1" />
    </div>
</div>
