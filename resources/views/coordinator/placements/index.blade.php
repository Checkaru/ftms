<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">التنسيبات</h2>
            <a href="{{ route('coordinator.placements.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                إضافة تنسيب
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">الطالب</th>
                            <th class="px-4 py-3 font-medium">المؤسسة</th>
                            <th class="px-4 py-3 font-medium">الفترة</th>
                            <th class="px-4 py-3 font-medium">المشرف الميداني</th>
                            <th class="px-4 py-3 font-medium">المشرف الأكاديمي</th>
                            <th class="px-4 py-3 font-medium">الحالة</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($placements as $placement)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ $placement->student->name }}
                                    @if ($placement->student->student_number)
                                        <span class="text-gray-400 text-xs">#{{ $placement->student->student_number }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $placement->organization->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $placement->period->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $placement->fieldSupervisor?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $placement->academicSupervisor?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs
                                        {{ match ($placement->status) {
                                            \App\Enums\PlacementStatus::Active => 'bg-blue-100 text-blue-800',
                                            \App\Enums\PlacementStatus::Completed => 'bg-green-100 text-green-800',
                                            \App\Enums\PlacementStatus::Withdrawn => 'bg-gray-100 text-gray-600',
                                        } }}">
                                        {{ $placement->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                    <a href="{{ route('messages.placement', $placement) }}"
                                       class="text-gray-500 hover:underline me-3">مناقشة</a>
                                    <a href="{{ route('coordinator.placements.edit', $placement) }}"
                                       class="text-indigo-600 hover:underline">تعديل</a>
                                    <x-delete-button :action="route('coordinator.placements.destroy', $placement)"
                                                     confirm="حذف التنسيب سيحذف كل سجلات الحضور المرتبطة به. متابعة؟" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-400">لا توجد تنسيبات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $placements->links() }}</div>
        </div>
    </div>
</x-app-layout>
