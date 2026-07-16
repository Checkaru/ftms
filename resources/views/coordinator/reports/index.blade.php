<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">تقرير الساعات</h2>
            @if ($period)
                <a href="{{ route('coordinator.reports.export', ['period' => $period->id]) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                    تصدير CSV
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <!-- Period filter -->
            <form method="GET" action="{{ route('coordinator.reports.index') }}" class="flex items-center gap-3">
                <label for="period" class="text-sm text-gray-600">الفترة:</label>
                <select id="period" name="period" onchange="this.form.submit()"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                    @foreach ($periods as $p)
                        <option value="{{ $p->id }}" @selected($period && $period->id === $p->id)>
                            {{ $p->name }}@if ($p->is_open) — مفتوحة @endif
                        </option>
                    @endforeach
                </select>
            </form>

            @if (! $period)
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-6 text-amber-800">
                    لا توجد فترات تدريب بعد.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full text-sm text-start">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">الطالب</th>
                                <th class="px-4 py-3 font-medium">المؤسسة</th>
                                <th class="px-4 py-3 font-medium">الساعات المعتمدة</th>
                                <th class="px-4 py-3 font-medium">الإنجاز</th>
                                <th class="px-4 py-3 font-medium">معلّق / مرفوض</th>
                                <th class="px-4 py-3 font-medium">ميداني</th>
                                <th class="px-4 py-3 font-medium">أكاديمي</th>
                                <th class="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($placements as $placement)
                                @php
                                    $hours = round(($placement->approved_minutes ?? 0) / 60, 1);
                                    $required = $period->required_hours;
                                    $percent = $required > 0 ? (int) min(100, round($hours / $required * 100)) : 0;
                                    $field = $placement->evaluations->firstWhere('kind', \App\Enums\EvaluationKind::Field);
                                    $academic = $placement->evaluations->firstWhere('kind', \App\Enums\EvaluationKind::Academic);
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ $placement->student->name }}
                                        @if ($placement->student->student_number)
                                            <span class="text-gray-400 text-xs">#{{ $placement->student->student_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $placement->organization->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $hours }} / {{ $required }}</td>
                                    <td class="px-4 py-3 w-40">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 flex-1 rounded-full bg-gray-100 overflow-hidden">
                                                <div class="h-2 rounded-full bg-green-500" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 w-9">{{ $percent }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $placement->pending_count }} / {{ $placement->rejected_count }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $field ? rtrim(rtrim(number_format($field->total, 1), '0'), '.') : '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $academic ? rtrim(rtrim(number_format($academic->total, 1), '0'), '.') : '—' }}</td>
                                    <td class="px-4 py-3 text-end whitespace-nowrap">
                                        <a href="{{ route('coordinator.reports.show', $placement) }}"
                                           class="text-indigo-600 hover:underline">التقرير</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-400">لا توجد تنسيبات في هذه الفترة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
