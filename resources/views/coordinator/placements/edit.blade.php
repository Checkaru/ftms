<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">تعديل تنسيب</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('coordinator.placements.update', $placement) }}">
                    @method('PUT')
                    @include('coordinator.placements._form')

                    <div class="mt-6 flex gap-3">
                        <x-primary-button>حفظ التعديلات</x-primary-button>
                        <a href="{{ route('coordinator.placements.index') }}"
                           class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
