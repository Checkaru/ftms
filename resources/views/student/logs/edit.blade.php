<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">تعديل سجل حضور</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if ($log->isRejected() && $log->reject_reason)
                <div class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    سبب الرفض: {{ $log->reject_reason }} — صحّح السجل لإعادته للمراجعة.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('student.logs.update', $log) }}">
                    @method('PUT')
                    @include('student.logs._form')

                    <div class="mt-6 flex gap-3">
                        <x-primary-button>حفظ التعديلات</x-primary-button>
                        <a href="{{ route('student.logs.index') }}"
                           class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
