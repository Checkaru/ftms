<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">رسالة جديدة</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($contacts->isEmpty())
                    <p class="text-gray-500">لا توجد جهات يمكنك مراسلتها حالياً.</p>
                @else
                    <form method="POST" action="{{ route('messages.storeDm') }}">
                        @csrf

                        <div>
                            <x-input-label for="recipient_id" value="إلى" />
                            <select id="recipient_id" name="recipient_id" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">— اختر المستلم —</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" @selected((int) old('recipient_id') === $contact->id)>
                                        {{ $contact->name }} — {{ $contact->role->label() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('recipient_id')" class="mt-1" />
                        </div>

                        <div class="mt-5">
                            <x-input-label for="body" value="الرسالة" />
                            <textarea id="body" name="body" rows="4" required maxlength="2000"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('body') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-1" />
                        </div>

                        <div class="mt-6 flex gap-3">
                            <x-primary-button>إرسال</x-primary-button>
                            <a href="{{ route('messages.index') }}"
                               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
