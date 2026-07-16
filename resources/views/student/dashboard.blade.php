<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">تدريبي الميداني</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! $placement)
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-6 text-amber-800">
                    لا يوجد لديك تنسيب تدريب فعّال في فترة مفتوحة حالياً. يرجى مراجعة منسّق التدريب.
                </div>
            @else
                @php
                    $approvedMinutes = $placement->approvedMinutes();
                    $approvedHours = round($approvedMinutes / 60, 1);
                    $required = $placement->period->required_hours;
                    $remaining = round(max(0, $required - $approvedHours), 1);
                    $percent = $required > 0 ? (int) min(100, round($approvedHours / $required * 100)) : 0;
                @endphp

                <!-- The one question above the fold: hours left -->
                <div class="rounded-lg bg-white shadow-sm p-6">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <div class="text-sm text-gray-500">الساعات المتبقية</div>
                            <div class="text-5xl font-bold text-gray-800 mt-1">{{ $remaining }}
                                <span class="text-lg font-normal text-gray-400">ساعة</span>
                            </div>
                        </div>
                        <a href="{{ route('student.logs.create') }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                            + تسجيل يوم
                        </a>
                    </div>

                    <div class="mt-5">
                        <div class="h-3 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-3 rounded-full bg-green-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="mt-2 flex justify-between text-sm text-gray-500">
                            <span>معتمدة: <strong class="text-gray-800">{{ $approvedHours }}</strong> من {{ $required }} ساعة</span>
                            <span>{{ $percent }}%</span>
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-gray-500 border-t pt-3 flex flex-wrap gap-x-6 gap-y-1">
                        <span>المؤسسة: <strong class="text-gray-700">{{ $placement->organization->name }}</strong></span>
                        <span>الفترة: <strong class="text-gray-700">{{ $placement->period->name }}</strong></span>
                        @if ($pendingCount > 0)
                            <span class="text-amber-700">{{ $pendingCount }} سجل بانتظار المراجعة</span>
                        @endif
                    </div>
                </div>

                <!-- Recent entries -->
                <div class="rounded-lg bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b flex items-center justify-between">
                        <h3 class="font-medium text-gray-700">أحدث السجلات</h3>
                        <a href="{{ route('student.logs.index') }}" class="text-sm text-indigo-600 hover:underline">عرض الكل</a>
                    </div>
                    <table class="min-w-full text-sm text-start">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-2 font-medium">التاريخ</th>
                                <th class="px-4 py-2 font-medium">الوقت</th>
                                <th class="px-4 py-2 font-medium">الساعات</th>
                                <th class="px-4 py-2 font-medium">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentLogs as $log)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $log->work_date->format('Y/m/d') }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ substr($log->check_in, 0, 5) }} – {{ substr($log->check_out, 0, 5) }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $log->hours }}</td>
                                    <td class="px-4 py-2">
                                        <x-log-status :status="$log->status" />
                                        @if ($log->isRejected() && $log->reject_reason)
                                            <div class="text-xs text-red-600 mt-1">{{ $log->reject_reason }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-400">لا توجد سجلات بعد. ابدأ بتسجيل يوم.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
