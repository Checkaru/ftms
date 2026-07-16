@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\LogStatus::Pending => 'bg-amber-100 text-amber-800',
        \App\Enums\LogStatus::Approved => 'bg-green-100 text-green-800',
        \App\Enums\LogStatus::Rejected => 'bg-red-100 text-red-800',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex px-2 py-0.5 rounded-full text-xs '.$classes]) }}>
    {{ $status->label() }}
</span>
