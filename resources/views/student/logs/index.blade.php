<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">سجلات الحضور</h2>
            @if ($placement)
                <a href="{{ route('student.logs.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                    + تسجيل يوم
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (! $placement)
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-6 text-amber-800">
                    لا يوجد لديك تنسيب تدريب فعّال في فترة مفتوحة حالياً.
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full text-sm text-start">
                        <thead class="bg-gray-50 text-gray-500">
                            <tr>
                                <th class="px-4 py-3 font-medium">التاريخ</th>
                                <th class="px-4 py-3 font-medium">الوقت</th>
                                <th class="px-4 py-3 font-medium">الساعات</th>
                                <th class="px-4 py-3 font-medium">المهام</th>
                                <th class="px-4 py-3 font-medium">الحالة</th>
                                <th class="px-4 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $log->work_date->format('Y/m/d') }}</td>
                                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ substr($log->check_in, 0, 5) }} – {{ substr($log->check_out, 0, 5) }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $log->hours }}</td>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $log->tasks }}">{{ $log->tasks }}</td>
                                    <td class="px-4 py-3">
                                        <x-log-status :status="$log->status" />
                                        @if ($log->isRejected() && $log->reject_reason)
                                            <div class="text-xs text-red-600 mt-1">سبب الرفض: {{ $log->reject_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-end whitespace-nowrap">
                                        @if ($log->isEditableByStudent())
                                            <a href="{{ route('student.logs.edit', $log) }}" class="text-indigo-600 hover:underline">تعديل</a>
                                            <x-delete-button :action="route('student.logs.destroy', $log)" />
                                        @else
                                            <span class="text-xs text-gray-400">مقفل</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-400">لا توجد سجلات بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
