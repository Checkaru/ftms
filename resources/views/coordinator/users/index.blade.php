<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">المستخدمون</h2>
            <a href="{{ route('coordinator.users.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                إضافة مستخدم
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
                            <th class="px-4 py-3 font-medium">البريد</th>
                            <th class="px-4 py-3 font-medium">الدور</th>
                            <th class="px-4 py-3 font-medium">المؤسسة / الرقم الجامعي</th>
                            <th class="px-4 py-3 font-medium">الحالة</th>
                            <th class="px-4 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->role->label() }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($user->isFieldSupervisor())
                                        {{ $user->organization?->name ?? '—' }}
                                    @elseif ($user->isStudent())
                                        {{ $user->student_number ? '#'.$user->student_number : '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-status-badge :active="$user->is_active" />
                                </td>
                                <td class="px-4 py-3 text-end whitespace-nowrap">
                                    <a href="{{ route('coordinator.users.edit', $user) }}"
                                       class="text-indigo-600 hover:underline">تعديل</a>
                                    @if ($user->id !== auth()->id())
                                        <x-delete-button :action="route('coordinator.users.destroy', $user)" />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">لا يوجد مستخدمون.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>
