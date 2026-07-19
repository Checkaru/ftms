@php
    $role = auth()->user()->role;
    $links = match ($role) {
        \App\Enums\UserRole::Coordinator => [
            ['route' => 'coordinator.dashboard', 'active' => 'coordinator.dashboard', 'label' => 'الرئيسية'],
            ['route' => 'coordinator.organizations.index', 'active' => 'coordinator.organizations.*', 'label' => 'المؤسسات'],
            ['route' => 'coordinator.periods.index', 'active' => 'coordinator.periods.*', 'label' => 'الفترات'],
            ['route' => 'coordinator.placements.index', 'active' => 'coordinator.placements.*', 'label' => 'التنسيبات'],
            ['route' => 'coordinator.users.index', 'active' => 'coordinator.users.*', 'label' => 'المستخدمون'],
            ['route' => 'coordinator.reports.index', 'active' => 'coordinator.reports.*', 'label' => 'التقارير'],
        ],
        \App\Enums\UserRole::Student => [
            ['route' => 'student.dashboard', 'active' => 'student.dashboard', 'label' => 'الرئيسية'],
            ['route' => 'student.logs.index', 'active' => 'student.logs.*', 'label' => 'سجلاتي'],
        ],
        \App\Enums\UserRole::FieldSupervisor => [
            ['route' => 'field.dashboard', 'active' => 'field.dashboard', 'label' => 'قائمة المراجعة'],
            ['route' => 'field.students.index', 'active' => 'field.students.*', 'label' => 'طلابي'],
        ],
        \App\Enums\UserRole::AcademicSupervisor => [
            ['route' => 'academic.dashboard', 'active' => 'academic.*', 'label' => 'طلابي'],
        ],
    };
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center font-bold text-gray-800">
                    نظام التدريب الميداني
                </div>

                <div class="hidden sm:flex sm:gap-6 sm:ms-10">
                    @foreach ($links as $link)
                        <x-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                            {{ $link['label'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="left" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-600 bg-white hover:text-gray-900 focus:outline-none">
                            <div>{{ auth()->user()->name }}</div>
                            <span class="ms-2 text-xs text-gray-400">{{ $role->label() }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">الملف الشخصي</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                تسجيل الخروج
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($links as $link)
                <x-responsive-nav-link :href="route($link['route'])" :active="request()->routeIs($link['active'])">
                    {{ $link['label'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ auth()->user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">الملف الشخصي</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        تسجيل الخروج
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
