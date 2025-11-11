<?php

use App\Services\DashboardService;
use Livewire\Volt\Component;

new class extends Component {
    public $dashboardData = [];
    public $loading = true;

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $this->loading = true;
        
        try {
            $dashboardService = app(DashboardService::class);
            $this->dashboardData = $dashboardService->getDashboardData(auth()->user());
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load dashboard data: ' . $e->getMessage());
            $this->dashboardData = [
                'role' => 'member',
                'title' => 'Dashboard',
                'stats' => [],
                'recent_activities' => collect(),
                'pending_approvals' => collect(),
                'quick_actions' => [],
                'charts' => [],
            ];
        } finally {
            $this->loading = false;
        }
    }

    public function refreshDashboard(): void
    {
        $this->loadDashboardData();
    }
}; ?>

<div>
    @if($loading)
        <div class="flex items-center justify-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>
    @else
        <div class="space-y-6">
            <!-- Header -->
            <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1.5">
                        <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                            {{ $dashboardData['title'] ?? 'Dashboard' }}
                        </flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                            Welcome back, {{ auth()->user()->name }}
                        </flux:text>
                    </div>
                    <div>
                        <flux:button variant="outline" wire:click="refreshDashboard" icon="arrow-path" class="gap-2">
                            Refresh
                        </flux:button>
                    </div>
                </div>
            </div>

            @if(auth()->user()?->member && !auth()->user()->member->is_complete)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 sm:p-5 dark:border-amber-900/40 dark:bg-amber-900/20">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <flux:icon name="sparkles" class="size-5 text-amber-600 dark:text-amber-300" />
                            <div class="space-y-1">
                                <flux:heading size="sm" class="font-semibold text-amber-900 dark:text-amber-200">
                                    Complete your registration
                                </flux:heading>
                                <flux:text class="text-sm text-amber-800 dark:text-amber-100">
                                    We need a few more details to activate your membership benefits. Submit them when you’re ready.
                                </flux:text>
                            </div>
                        </div>
                        <flux:button href="{{ route('members.complete') }}" icon="arrow-right" variant="primary" class="w-full sm:w-auto" wire:navigate>
                            Continue registration
                        </flux:button>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            @if(!empty($dashboardData['stats']))
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                    @foreach($dashboardData['stats'] as $stat)
                        @php
                            $color = $stat['color'] ?? 'indigo';
                            $iconBg = "bg-{$color}-100";
                            $iconDarkBg = "dark:bg-{$color}-900/20";
                            $iconText = "text-{$color}-600";
                            $iconDarkText = "dark:text-{$color}-400";
                        @endphp
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg {{ $iconBg }} {{ $iconDarkBg }} p-2 sm:p-3">
                                    <flux:icon name="{{ $stat['icon'] }}" class="size-5 sm:size-6 {{ $iconText }} {{ $iconDarkText }}" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                        {{ $stat['title'] }}
                                    </flux:text>
                                    <flux:heading size="lg" class="font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                        {{ $stat['value'] }}
                                    </flux:heading>
                                    @if($stat['trend'])
                                        <flux:text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                            {{ $stat['trend'] }}
                                        </flux:text>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Activities -->
                <div class="lg:col-span-2">
                    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                        <div class="px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                            <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                                Recent Activities
                            </flux:heading>
                        </div>
                        <div class="p-4 sm:p-6">
                            @if((is_array($dashboardData['recent_activities']) ? count($dashboardData['recent_activities']) > 0 : $dashboardData['recent_activities']->isNotEmpty()))
                                <div class="flow-root">
                                    <ul class="-mb-8">
                                        @foreach($dashboardData['recent_activities'] as $index => $activity)
                                            <li>
                                                <div class="relative pb-8">
                                                    @if(!$loop->last)
                                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-neutral-200 dark:bg-neutral-700" aria-hidden="true"></span>
                                                    @endif
                                                    <div class="relative flex items-start gap-3">
                                                        <div>
                                                            <span class="h-8 w-8 rounded-full bg-neutral-400 dark:bg-neutral-600 flex items-center justify-center ring-8 ring-white dark:ring-zinc-800">
                                                                <flux:icon name="user" class="w-4 h-4 text-white" />
                                                            </span>
                                                        </div>
                                                        <div class="min-w-0 flex flex-1 items-start justify-between gap-4 pt-1.5">
                                                            <div>
                                                                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $activity->user->name ?? 'System' }}</span>
                                                                    {{ $activity->action }}
                                                                    <span class="font-medium text-gray-900 dark:text-white">{{ $activity->entity_type }}</span>
                                                                </p>
                                                            </div>
                                                            <div class="text-right text-sm whitespace-nowrap text-neutral-500 dark:text-neutral-400">
                                                                <time datetime="{{ $activity->created_at->toISOString() }}">
                                                                    {{ $activity->created_at->diffForHumans() }}
                                                                </time>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <div class="text-center py-6">
                                    <flux:icon name="clock" class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" />
                                    <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                                        No recent activities
                                    </flux:heading>
                                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                        Activities will appear here as they happen.
                                    </flux:text>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals & Quick Actions -->
                <div class="space-y-6">
                    <!-- Pending Approvals -->
                    @if((is_array($dashboardData['pending_approvals']) ? count($dashboardData['pending_approvals']) > 0 : $dashboardData['pending_approvals']->isNotEmpty()))
                        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                                <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                                    Pending Approvals
                                </flux:heading>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="space-y-4">
                                    @foreach($dashboardData['pending_approvals'] as $approval)
                                        <div class="flex items-center justify-between gap-3 rounded-lg border border-yellow-100 bg-yellow-50 p-3 dark:border-yellow-900/30 dark:bg-yellow-900/20">
                                            <div class="min-w-0 flex-1">
                                                <flux:text class="text-sm font-medium text-neutral-900 dark:text-white">
                                                    {{ $approval->member->full_name ?? 'Unknown Member' }}
                                                </flux:text>
                                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                                                    {{ ucfirst($approval->status) }} - ₦{{ number_format($approval->amount ?? 0, 2) }}
                                                </flux:text>
                                            </div>
                                            <flux:button size="sm" variant="primary" :href="route('loans.show', $approval->id)" wire:navigate>
                                                Review
                                            </flux:button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Quick Actions -->
                    @if(!empty($dashboardData['quick_actions']))
                        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                            <div class="px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                                <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                                    Quick Actions
                                </flux:heading>
                            </div>
                            <div class="p-4 sm:p-6">
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($dashboardData['quick_actions'] as $action)
                                        <flux:button 
                                            variant="outline" 
                                            :href="$action['url']" 
                                            wire:navigate
                                            class="justify-start gap-2"
                                        >
                                            <flux:icon name="{{ $action['icon'] }}" class="size-4" />
                                            {{ $action['title'] }}
                                        </flux:button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Charts Section -->
            @if(!empty($dashboardData['charts']))
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="px-4 sm:px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                        <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                            Analytics
                        </flux:heading>
                    </div>
                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach($dashboardData['charts'] as $chartName => $chartData)
                                <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-800 dark:bg-neutral-900">
                                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white capitalize">
                                        {{ str_replace('_', ' ', $chartName) }}
                                    </flux:heading>
                                    <!-- Placeholder for chart - in a real implementation, you'd use Chart.js or similar -->
                                    <div class="flex h-64 items-center justify-center rounded-lg border border-dashed border-neutral-300 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                                        <div class="text-center">
                                            <flux:icon name="chart-bar" class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" />
                                            <flux:text class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
                                                Chart: {{ $chartName }}
                                            </flux:text>
                                            <flux:text class="text-xs text-neutral-400 dark:text-neutral-500">
                                                Labels: {{ implode(', ', $chartData['labels'] ?? []) }}
                                            </flux:text>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
