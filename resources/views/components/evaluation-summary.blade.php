@props(['evaluation'])

@php
    $rubric = config('training.rubrics.'.$evaluation->kind->value, []);
    $maxTotal = array_sum(array_column($rubric, 'max'));
    $total = rtrim(rtrim(number_format($evaluation->total, 1), '0'), '.');
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 p-4']) }}>
    <div class="flex items-center justify-between">
        <span class="font-medium text-gray-800">{{ $evaluation->kind->label() }}</span>
        <span class="text-sm">
            <span class="font-semibold text-gray-800">{{ $total }}</span>
            <span class="text-gray-400">/ {{ $maxTotal }}</span>
        </span>
    </div>

    <ul class="mt-3 space-y-1 text-sm text-gray-600">
        @foreach ($evaluation->scores as $key => $value)
            <li class="flex justify-between">
                <span>{{ $rubric[$key]['label'] ?? $key }}</span>
                <span class="text-gray-500">{{ $value }} / {{ $rubric[$key]['max'] ?? '—' }}</span>
            </li>
        @endforeach
    </ul>

    @if ($evaluation->comments)
        <p class="mt-3 text-sm text-gray-600 border-t pt-2">{{ $evaluation->comments }}</p>
    @endif

    <div class="mt-2 text-xs text-gray-400">
        {{ $evaluation->evaluator?->name }}
        @if ($evaluation->submitted_at) · {{ $evaluation->submitted_at->format('Y/m/d') }} @endif
    </div>
</div>
