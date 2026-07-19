<x-app-layout>
    <x-slot name="header">
        @php
            $me = auth()->user();

            if ($conversation->isPlacementThread()) {
                $title = 'مناقشة تدريب: '.$conversation->placement->student->name;
                $subtitle = $conversation->placement->organization->name.' — '.$conversation->placement->period->name;
            } else {
                $other = $conversation->otherParticipant($me);
                $title = $other?->name ?? 'مستخدم محذوف';
                $subtitle = $other?->role->label();
            }
        @endphp
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            <a href="{{ route('messages.index') }}" class="text-sm text-gray-500 hover:text-gray-800">→ كل الرسائل</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <!-- Thread -->
            <div class="bg-white shadow-sm rounded-lg p-5 space-y-3">
                @forelse ($messages as $message)
                    @php $mine = $message->sender_id === $me->id; @endphp
                    <div class="flex {{ $mine ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[80%] rounded-lg px-4 py-2 {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                            @unless ($mine)
                                <div class="text-xs font-medium {{ $mine ? 'text-indigo-200' : 'text-gray-500' }}">
                                    {{ $message->sender?->name ?? 'مستخدم محذوف' }}
                                    @if ($message->sender)
                                        · {{ $message->sender->role->label() }}
                                    @endif
                                </div>
                            @endunless
                            <div class="whitespace-pre-line break-words text-sm mt-0.5">{{ $message->body }}</div>
                            <div class="text-[11px] mt-1 {{ $mine ? 'text-indigo-200' : 'text-gray-400' }}">
                                {{ $message->created_at->format('Y/m/d H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-400 py-6">لا رسائل بعد — ابدأ الحوار.</p>
                @endforelse
            </div>

            <!-- Composer -->
            <form method="POST" action="{{ route('messages.store', $conversation) }}"
                  class="bg-white shadow-sm rounded-lg p-4">
                @csrf
                <textarea name="body" rows="2" required maxlength="2000" placeholder="اكتب رسالتك…"
                          class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">{{ old('body') }}</textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-1" />
                <div class="mt-3 flex justify-start">
                    <x-primary-button>إرسال</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
