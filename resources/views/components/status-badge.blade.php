@props(['active' => true, 'on' => 'نشط', 'off' => 'موقوف'])

<span {{ $attributes->merge(['class' => 'inline-flex px-2 py-0.5 rounded-full text-xs '.($active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600')]) }}>
    {{ $active ? $on : $off }}
</span>
