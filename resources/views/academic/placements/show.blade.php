<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $placement->student->name }}
                @if ($placement->student->student_number)
                    <span class="text-gray-400 text-sm">#{{ $placement->student->student_number }}</span>
                @endif
            </h2>
            <a href="{{ route('academic.evaluation.edit', $placement) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                {{ $academicEvaluation ? 'تعديل التقييم' : 'إدخال التقييم' }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $approvedMinutes = $placement->approvedMinutes();
                $approvedHours = round($approvedMinutes / 60, 1);
                $required = $placement->period->required_hours;
                $percent = $required > 0 ? (int) min(100, round($approvedHours / $required * 100)) : 0;
            @endphp

            <!-- Summary -->
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex flex-wrap gap-x-8 gap-y-2 text-sm text-gray-600">
                    <span>المؤسسة: <strong class="text-gray-800">{{ $placement->organization->name }}</strong></span>
                    <span>الفترة: <strong class="text-gray-800">{{ $placement->period->name }}</strong></span>
                    <span>الحالة: <strong class="text-gray-800">{{ $placement->status->label() }}</strong></span>
                </div>
                <div class="mt-4">
                    <div class="h-3 w-full rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-3 rounded-full bg-green-500" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        الساعات المعتمدة: <strong class="text-gray-800">{{ $approvedHours }}</strong> من {{ $required }} ساعة ({{ $percent }}%)
                    </div>
                </div>
            </div>

            <!-- Evaluations -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if ($fieldEvaluation)
                    <x-evaluation-summary :evaluation="$fieldEvaluation" />
                @else
                    <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-400">
                        لم يُقدَّم التقييم الميداني بعد.
                    </div>
                @endif

                @if ($academicEvaluation)
                    <x-evaluation-summary :evaluation="$academicEvaluation" />
                @else
                    <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-400">
                        لم تُدخِل التقييم الأكاديمي بعد.
                    </div>
                @endif
            </div>

            <!-- Full logs (read-only) -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-5 py-3 border-b font-medium text-gray-700">سجلات الحضور ({{ $logs->count() }})</div>
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">التاريخ</th>
                            <th class="px-4 py-2 font-medium">الوقت</th>
                            <th class="px-4 py-2 font-medium">الساعات</th>
                            <th class="px-4 py-2 font-medium">المهام</th>
                            <th class="px-4 py-2 font-medium">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $log->work_date->format('Y/m/d') }}</td>
                                <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ substr($log->check_in, 0, 5) }} – {{ substr($log->check_out, 0, 5) }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $log->hours }}</td>
                                <td class="px-4 py-2 text-gray-600 max-w-xs truncate" title="{{ $log->tasks }}">{{ $log->tasks }}</td>
                                <td class="px-4 py-2">
                                    <x-log-status :status="$log->status" />
                                    @if ($log->isRejected() && $log->reject_reason)
                                        <div class="text-xs text-red-600 mt-1">{{ $log->reject_reason }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">لا توجد سجلات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <a href="{{ route('academic.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-800">→ العودة إلى قائمة الطلاب</a>
            </div>
        </div>
    </div>
</x-app-layout>
