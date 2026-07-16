<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">طلابي</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">الطالب</th>
                            <th class="px-4 py-3 font-medium">المؤسسة</th>
                            <th class="px-4 py-3 font-medium">الفترة</th>
                            <th class="px-4 py-3 font-medium">الساعات المعتمدة</th>
                            <th class="px-4 py-3 font-medium">الدرجة الأكاديمية</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($placements as $placement)
                            @php
                                $hours = round(($placement->approved_minutes ?? 0) / 60, 1);
                                $required = $placement->period->required_hours;
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
                                <td class="px-4 py-3 text-gray-600">{{ $placement->period->name }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $hours }} / {{ $required }}
                                    @if ($hours >= $required)
                                        <span class="text-green-600 text-xs">مكتملة</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($academic)
                                        <span class="font-semibold text-gray-800">{{ rtrim(rtrim(number_format($academic->total, 1), '0'), '.') }}</span>
                                        <span class="text-gray-400 text-xs">/ 100</span>
                                    @else
                                        <span class="text-amber-600 text-xs">لم يُقيّم</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                    <a href="{{ route('academic.placements.show', $placement) }}"
                                       class="text-indigo-600 hover:underline">عرض</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">لا يوجد طلاب مُسندون إليك.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
