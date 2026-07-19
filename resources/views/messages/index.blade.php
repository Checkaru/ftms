<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">الرسائل</h2>
            <a href="{{ route('messages.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                + رسالة جديدة
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg divide-y divide-gray-100 overflow-hidden">
                @forelse ($conversations as $conversation)
                    @php
                        $me = auth()->user();
                        $unread = $conversation->hasUnreadFor($me);

                        if ($conversation->isPlacementThread()) {
                            $title = 'مناقشة تدريب: '.$conversation->placement->student->name;
                            $subtitle = $conversation->placement->organization->name;
                        } else {
                            $other = $conversation->otherParticipant($me);
                            $title = $other?->name ?? 'مستخدم محذوف';
                            $subtitle = $other?->role->label();
                        }

                        $latest = $conversation->latestMessage;
                    @endphp

                    <a href="{{ route('messages.show', $conversation) }}"
                       class="block px-5 py-4 hover:bg-gray-50 {{ $unread ? 'bg-indigo-50/40' : '' }}">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-medium {{ $unread ? 'text-gray-900' : 'text-gray-700' }}">
                                {{ $title }}
                                @if ($unread)
                                    <span class="ms-1 inline-block w-2 h-2 rounded-full bg-red-500"></span>
                                @endif
                            </span>
                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                {{ $latest?->created_at->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        <div class="mt-0.5 flex items-baseline justify-between gap-3">
                            <span class="text-sm text-gray-500 truncate">
                                @if ($latest)
                                    @if ($latest->sender_id === $me->id)<span class="text-gray-400">أنت:</span>@endif
                                    {{ \Illuminate\Support\Str::limit($latest->body, 80) }}
                                @else
                                    <span class="text-gray-400">لا رسائل بعد</span>
                                @endif
                            </span>
                            @if ($subtitle)
                                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $subtitle }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-gray-400">
                        لا توجد محادثات بعد. ابدأ <a href="{{ route('messages.create') }}" class="text-indigo-600 hover:underline">رسالة جديدة</a>.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
