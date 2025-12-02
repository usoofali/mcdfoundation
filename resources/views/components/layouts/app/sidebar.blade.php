<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <flux:navlist variant="outline">
            <flux:navlist.group :heading="__('Platform')" class="grid">
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Members')" class="grid">
                <flux:navlist.item icon="users" :href="route('members.index')"
                    :current="request()->routeIs('members.*')" wire:navigate>{{ __('All Members') }}</flux:navlist.item>
                <flux:navlist.item icon="plus" :href="route('members.create')"
                    :current="request()->routeIs('members.create')" wire:navigate>{{ __('Register Member') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Contributions')" class="grid">
                <flux:navlist.item icon="currency-dollar" :href="route('contributions.index')"
                    :current="request()->routeIs('contributions.*')" wire:navigate>{{ __('All Contributions') }}
                </flux:navlist.item>
                <flux:navlist.item icon="plus" :href="route('contributions.create')"
                    :current="request()->routeIs('contributions.create')" wire:navigate>{{ __('Record Contribution') }}
                </flux:navlist.item>

                @if(auth()->user()->hasPermission('submit_contributions'))
                    <flux:navlist.item icon="arrow-up-tray" :href="route('contributions.submit')"
                        :current="request()->routeIs('contributions.submit')" wire:navigate>{{ __('Submit Contribution') }}
                    </flux:navlist.item>
                @endif

                @if(auth()->user()->hasPermission('confirm_contributions'))
                    <flux:navlist.item icon="check-circle" :href="route('contributions.verify')"
                        :current="request()->routeIs('contributions.verify')" wire:navigate>{{ __('Verify Contributions') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>


            <flux:navlist.group :heading="__('Loans')" class="grid">
                <flux:navlist.item icon="banknotes" :href="route('loans.index')"
                    :current="request()->routeIs('loans.*')" wire:navigate>{{ __('All Loans') }}</flux:navlist.item>
                <flux:navlist.item icon="plus" :href="route('loans.create')"
                    :current="request()->routeIs('loans.create')" wire:navigate>{{ __('Apply for Loan') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Health Claims')" class="grid">
                <flux:navlist.item icon="heart" :href="route('health-claims.index')"
                    :current="request()->routeIs('health-claims.*')" wire:navigate>{{ __('All Claims') }}
                </flux:navlist.item>
                <flux:navlist.item icon="plus" :href="route('health-claims.create')"
                    :current="request()->routeIs('health-claims.create')" wire:navigate>{{ __('Submit Claim') }}
                </flux:navlist.item>
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Programs')" class="grid">
                <flux:navlist.item icon="academic-cap" :href="route('programs.index')"
                    :current="request()->routeIs('programs.*')" wire:navigate>{{ __('All Programs') }}
                </flux:navlist.item>

                @if(auth()->user()->hasPermission('manage_programs'))
                    <flux:navlist.item icon="plus" :href="route('programs.create')"
                        :current="request()->routeIs('programs.create')" wire:navigate>{{ __('Create Program') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Cashout')" class="grid">
                @if(auth()->user()->hasPermission('view_cashout'))
                    {{-- Staff can see all cashouts --}}
                    <flux:navlist.item icon="banknotes" :href="route('admin.cashout.index')"
                        :current="request()->routeIs('admin.cashout.*')" wire:navigate>{{ __('All Cashouts') }}
                    </flux:navlist.item>
                @elseif(auth()->user()->hasPermission('request_cashout') && auth()->user()->member)
                    {{-- Members see their own requests --}}
                    <flux:navlist.item icon="banknotes" :href="route('cashout.index')"
                        :current="request()->routeIs('cashout.*')" wire:navigate>{{ __('My Requests') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>

            <flux:navlist.group :heading="__('Reports')" class="grid">
                <flux:navlist.item icon="chart-bar" :href="route('reports.index')"
                    :current="request()->routeIs('reports.*')" wire:navigate>{{ __('All Reports') }}</flux:navlist.item>
            </flux:navlist.group>

            @if(auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('System Admin'))
                <flux:navlist.group :heading="__('Administration')" class="grid">
                    <flux:navlist.item icon="users" :href="route('admin.users.index')"
                        :current="request()->routeIs('admin.users.*')" wire:navigate>{{ __('User Management') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="shield-check" :href="route('admin.roles.index')"
                        :current="request()->routeIs('admin.roles.*')" wire:navigate>{{ __('Role Management') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="currency-dollar" :href="route('admin.contribution-plans.index')"
                        :current="request()->routeIs('admin.contribution-plans.*')" wire:navigate>
                        {{ __('Contribution Plans') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="heart" :href="route('admin.healthcare-providers.index')"
                        :current="request()->routeIs('admin.healthcare-providers.*')" wire:navigate>
                        {{ __('Healthcare Providers') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth" :href="route('admin.settings.index')"
                        :current="request()->routeIs('admin.settings.*')" wire:navigate>{{ __('System Settings') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif
        </flux:navlist>

        <flux:spacer />

        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon-trailing="chevrons-up-down" data-test="sidebar-menu-button" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full"
                        data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    <flux:main>
        {{ $slot }}
    </flux:main>

    @include('components.partials.flash-alerts')
    @include('components.partials.livewire-notify-alerts')

    @fluxScripts
</body>

</html>