<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between print:hidden">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                تقرير ساعات — {{ $placement->student->name }}
            </h2>
            <div class="flex gap-3">
                <button onclick="window.print()"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                    طباعة
                </button>
                <a href="{{ route('coordinator.reports.index', ['period' => $placement->period_id]) }}"
                   class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">عودة</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 print:py-0">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6" id="report">

            @php
                $approvedMinutes = $placement->approvedMinutes();
                $approvedHours = round($approvedMinutes / 60, 1);
                $required = $placement->period->required_hours;
                $percent = $required > 0 ? (int) min(100, round($approvedHours / $required * 100)) : 0;
                $approvedLogs = $logs->where('status', \App\Enums\LogStatus::Approved);
            @endphp

            <!-- Print-only letterhead -->
            <div class="hidden print:block text-center border-b pb-4">
                <h1 class="text-xl font-bold">نظام التدريب الميداني — تقرير ساعات الطالب</h1>
                <p class="text-sm mt-1">تاريخ الإصدار: {{ now()->format('Y/m/d') }}</p>
            </div>

            <!-- Student / placement facts -->
            <div class="bg-white shadow-sm rounded-lg p-6 print:shadow-none print:border print:rounded-none">
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">الطالب</dt>
                        <dd class="font-medium text-gray-900">{{ $placement->student->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">الرقم الجامعي</dt>
                        <dd class="font-medium text-gray-900">{{ $placement->student->student_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">المؤسسة</dt>
                        <dd class="font-medium text-gray-900">{{ $placement->organization->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">الفترة</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $placement->period->name }}
                            ({{ $placement->period->starts_on->format('Y/m/d') }} – {{ $placement->period->ends_on->format('Y/m/d') }})
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">المشرف الميداني</dt>
                        <dd class="font-medium text-gray-900">{{ $placement->fieldSupervisor?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">المشرف الأكاديمي</dt>
                        <dd class="font-medium text-gray-900">{{ $placement->academicSupervisor?->name ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-5 border-t pt-4 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $approvedHours }}</div>
                        <div class="text-xs text-gray-500">ساعة معتمدة</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900">{{ $required }}</div>
                        <div class="text-xs text-gray-500">ساعة مطلوبة</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold {{ $percent >= 100 ? 'text-green-600' : 'text-gray-900' }}">{{ $percent }}%</div>
                        <div class="text-xs text-gray-500">نسبة الإنجاز</div>
                    </div>
                </div>
            </div>

            <!-- Evaluations -->
            @if ($fieldEvaluation || $academicEvaluation)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 print:grid-cols-2">
                    @if ($fieldEvaluation)
                        <x-evaluation-summary :evaluation="$fieldEvaluation" class="bg-white shadow-sm print:shadow-none" />
                    @endif
                    @if ($academicEvaluation)
                        <x-evaluation-summary :evaluation="$academicEvaluation" class="bg-white shadow-sm print:shadow-none" />
                    @endif
                </div>
            @endif

            <!-- Approved log detail (what the hours are made of) -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden print:shadow-none print:border print:rounded-none">
                <div class="px-5 py-3 border-b font-medium text-gray-700">
                    السجلات المعتمدة ({{ $approvedLogs->count() }})
                </div>
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">التاريخ</th>
                            <th class="px-4 py-2 font-medium">الحضور</th>
                            <th class="px-4 py-2 font-medium">الانصراف</th>
                            <th class="px-4 py-2 font-medium">الساعات</th>
                            <th class="px-4 py-2 font-medium">المهام</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($approvedLogs as $log)
                            <tr>
                                <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $log->work_date->format('Y/m/d') }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ substr($log->check_in, 0, 5) }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ substr($log->check_out, 0, 5) }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $log->hours }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $log->tasks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-400">لا توجد سجلات معتمدة بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($approvedLogs->isNotEmpty())
                        <tfoot class="bg-gray-50 font-medium text-gray-800">
                            <tr>
                                <td class="px-4 py-2" colspan="3">الإجمالي</td>
                                <td class="px-4 py-2">{{ $approvedHours }}</td>
                                <td class="px-4 py-2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Non-approved entries, for transparency (never counted) -->
            @php $otherLogs = $logs->where('status', '!=', \App\Enums\LogStatus::Approved); @endphp
            @if ($otherLogs->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg overflow-hidden print:shadow-none print:border print:rounded-none">
                    <div class="px-5 py-3 border-b font-medium text-gray-700">
                        سجلات غير معتمدة — لا تُحتسب ({{ $otherLogs->count() }})
                    </div>
                    <table class="min-w-full text-sm text-start">
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($otherLogs as $log)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700 whitespace-nowrap">{{ $log->work_date->format('Y/m/d') }}</td>
                                    <td class="px-4 py-2 text-gray-500 whitespace-nowrap">{{ substr($log->check_in, 0, 5) }} – {{ substr($log->check_out, 0, 5) }}</td>
                                    <td class="px-4 py-2"><x-log-status :status="$log->status" /></td>
                                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $log->reject_reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Print-only signature block -->
            <div class="hidden print:grid grid-cols-3 gap-8 pt-10 text-center text-sm">
                <div class="border-t pt-2">توقيع المشرف الميداني</div>
                <div class="border-t pt-2">توقيع المشرف الأكاديمي</div>
                <div class="border-t pt-2">توقيع منسق التدريب</div>
            </div>
        </div>
    </div>
</x-app-layout>
