<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">لوحة المنسق</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if ($openPeriod)
                <div class="rounded-lg bg-white shadow-sm p-4 text-sm text-gray-700">
                    الفترة المفتوحة حالياً:
                    <strong>{{ $openPeriod->name }}</strong>
                    ({{ $openPeriod->required_hours }} ساعة مطلوبة،
                    {{ $openPeriod->starts_on->format('Y/m/d') }} – {{ $openPeriod->ends_on->format('Y/m/d') }})
                </div>
            @else
                <div class="rounded-lg bg-amber-50 border border-amber-200 shadow-sm p-4 text-sm text-amber-800">
                    لا توجد فترة تدريب مفتوحة. افتح فترة من <a href="{{ route('coordinator.periods.index') }}" class="underline">إدارة الفترات</a>.
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @foreach ([
                    ['المؤسسات', $counts['organizations'], 'coordinator.organizations.index'],
                    ['الفترات', $counts['periods'], 'coordinator.periods.index'],
                    ['التنسيبات', $counts['placements'], 'coordinator.placements.index'],
                    ['المستخدمون', $counts['users'], 'coordinator.users.index'],
                    ['سجلات معلّقة', $counts['pendingLogs'], null],
                ] as [$label, $value, $route])
                    <a @if ($route) href="{{ route($route) }}" @endif
                       class="block rounded-lg bg-white shadow-sm p-5 {{ $route ? 'hover:shadow transition' : '' }}">
                        <div class="text-3xl font-bold text-gray-800">{{ $value }}</div>
                        <div class="mt-1 text-sm text-gray-500">{{ $label }}</div>
                    </a>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
