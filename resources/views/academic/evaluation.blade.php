<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            التقييم الأكاديمي — {{ $placement->student->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @php $maxTotal = array_sum(array_column($rubric, 'max')); @endphp

            <div class="bg-white shadow-sm rounded-lg p-6"
                 x-data="{
                    scores: {
                        @foreach ($rubric as $key => $meta)
                            '{{ $key }}': Number('{{ old('scores.'.$key, $evaluation->scores[$key] ?? 0) }}'),
                        @endforeach
                    },
                    get total() { return Object.values(this.scores).reduce((a, b) => a + (Number(b) || 0), 0); }
                 }">

                <form method="POST" action="{{ route('academic.evaluation.update', $placement) }}">
                    @method('PUT')
                    @csrf

                    <div class="space-y-5">
                        @foreach ($rubric as $key => $meta)
                            <div>
                                <div class="flex items-center justify-between">
                                    <x-input-label :for="'score_'.$key" :value="$meta['label']" />
                                    <span class="text-xs text-gray-400">الحد الأقصى {{ $meta['max'] }}</span>
                                </div>
                                <input id="score_{{ $key }}" type="number" name="scores[{{ $key }}]"
                                       min="0" max="{{ $meta['max'] }}" required
                                       x-model.number="scores['{{ $key }}']"
                                       class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <x-input-error :messages="$errors->get('scores.'.$key)" class="mt-1" />
                            </div>
                        @endforeach

                        <div>
                            <x-input-label for="comments" value="ملاحظات (اختياري)" />
                            <textarea id="comments" name="comments" rows="3" maxlength="2000"
                                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('comments', $evaluation?->comments) }}</textarea>
                            <x-input-error :messages="$errors->get('comments')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t pt-4">
                        <div class="text-sm text-gray-600">
                            الدرجة:
                            <span class="text-2xl font-bold text-gray-800" x-text="total"></span>
                            <span class="text-gray-400">/ {{ $maxTotal }}</span>
                        </div>
                        <div class="flex gap-3">
                            <x-primary-button>حفظ التقييم</x-primary-button>
                            <a href="{{ route('academic.placements.show', $placement) }}"
                               class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">إلغاء</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
