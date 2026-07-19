<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            طلابي
            @if (auth()->user()->organization)
                <span class="text-sm font-normal text-gray-400">— {{ auth()->user()->organization->name }}</span>
            @endif
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">الطالب</th>
                            <th class="px-4 py-3 font-medium">الفترة</th>
                            <th class="px-4 py-3 font-medium">الساعات المعتمدة</th>
                            <th class="px-4 py-3 font-medium">بانتظار المراجعة</th>
                            <th class="px-4 py-3 font-medium">التقييم الميداني</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($placements as $placement)
                            @php
                                $hours = round(($placement->approved_minutes ?? 0) / 60, 1);
                                $required = $placement->period->required_hours;
                                $fieldEval = $placement->evaluations->firstWhere('kind', \App\Enums\EvaluationKind::Field);
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ $placement->student->name }}
                                    @if ($placement->student->student_number)
                                        <span class="text-gray-400 text-xs">#{{ $placement->student->student_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $placement->period->name }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $hours }} / {{ $required }}</td>
                                <td class="px-4 py-3">
                                    @if ($placement->pending_count > 0)
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">
                                            {{ $placement->pending_count }} سجل
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($fieldEval)
                                        <span class="font-semibold text-gray-800">{{ rtrim(rtrim(number_format($fieldEval->total, 1), '0'), '.') }}</span>
                                        <span class="text-gray-400 text-xs">/ 100</span>
                                    @else
                                        <span class="text-amber-600 text-xs">لم يُقيّم</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                    <a href="{{ route('field.students.show', $placement) }}"
                                       class="text-indigo-600 hover:underline">السجلات</a>
                                    <a href="{{ route('field.students.evaluation.edit', $placement) }}"
                                       class="text-indigo-600 hover:underline ms-3">{{ $fieldEval ? 'تعديل التقييم' : 'التقييم' }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">لا يوجد طلاب في مؤسستك.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
