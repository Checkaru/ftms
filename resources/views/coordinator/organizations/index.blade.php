<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">المؤسسات</h2>
            <a href="{{ route('coordinator.organizations.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                إضافة مؤسسة
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full text-sm text-start">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">الاسم</th>
                            <th class="px-4 py-3 font-medium">القطاع</th>
                            <th class="px-4 py-3 font-medium">جهة الاتصال</th>
                            <th class="px-4 py-3 font-medium">التنسيبات</th>
                            <th class="px-4 py-3 font-medium">الحالة</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($organizations as $organization)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $organization->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $organization->sector ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $organization->contact_name ?? '—' }}
                                    @if ($organization->contact_phone)
                                        <span class="text-gray-400">· {{ $organization->contact_phone }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $organization->placements_count }}</td>
                                <td class="px-4 py-3">
                                    <x-status-badge :active="$organization->is_active" />
                                </td>
                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                    <a href="{{ route('coordinator.organizations.edit', $organization) }}"
                                       class="text-indigo-600 hover:underline">تعديل</a>
                                    <x-delete-button :action="route('coordinator.organizations.destroy', $organization)" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">لا توجد مؤسسات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $organizations->links() }}</div>
        </div>
    </div>
</x-app-layout>
