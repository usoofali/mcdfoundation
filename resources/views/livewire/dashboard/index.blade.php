<?php

use App\Services\DashboardService;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $dashboardData = [];

    public $loading = true;

    public $activitiesPerPage = 10;

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
            \Log::error('Failed to load dashboard data', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to load dashboard data. Please try again later.',
            ]);

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

    public function getActivitiesProperty()
    {
        $activities = $this->dashboardData['recent_activities'] ?? collect();

        if ($activities instanceof \Illuminate\Support\Collection) {
            $page = $this->getPage();
            $perPage = $this->activitiesPerPage;
            $offset = ($page - 1) * $perPage;

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $activities->slice($offset, $perPage)->values(),
                $activities->count(),
                $perPage,
                $page,
                ['path' => request()->url(), 'pageName' => 'page']
            );
        }

        return $activities;
    }

    public function refreshDashboard(): void
    {
        $this->resetPage();
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
                        <flux:button 
                            variant="outline" 
                            wire:click="refreshDashboard" 
                            wire:loading.attr="disabled"
                            wire:target="refreshDashboard"
                            class="gap-2"
                            aria-label="Refresh dashboard data"
                        >
                            <flux:icon 
                                name="arrow-path" 
                                class="size-4"
                                wire:loading.class="animate-spin"
                                wire:target="refreshDashboard"
                                aria-hidden="true"
                            />
                            <span wire:loading.remove wire:target="refreshDashboard">Refresh</span>
                            <span wire:loading wire:target="refreshDashboard" aria-live="polite">Refreshing...</span>
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
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
                            @php
                                $activities = $this->activities;
                            @endphp
                            @if($activities->count() > 0)
                                <div class="flow-root">
                                    <ul class="-mb-8">
                                        @foreach($activities as $index => $activity)
                                            @php
                                                $entityType = strtolower(class_basename($activity->entity_type ?? ''));
                                                $action = strtolower($activity->action ?? '');
                                                
                                                // Map entity types to icons
                                                $iconMap = [
                                                    'member' => 'user',
                                                    'contribution' => 'currency-dollar',
                                                    'loan' => 'banknotes',
                                                    'healthclaim' => 'heart',
                                                    'user' => 'users',
                                                    'fundledger' => 'wallet',
                                                    'auditlog' => 'document-text',
                                                ];
                                                
                                                // Map actions to icons if entity type not found
                                                if (!isset($iconMap[$entityType])) {
                                                    $actionMap = [
                                                        'created' => 'plus-circle',
                                                        'updated' => 'pencil',
                                                        'deleted' => 'trash',
                                                        'approved' => 'check-circle',
                                                        'rejected' => 'x-circle',
                                                    ];
                                                    $icon = $actionMap[$action] ?? 'document-text';
                                                } else {
                                                    $icon = $iconMap[$entityType];
                                                }
                                            @endphp
                                            <li>
                                                <div class="relative pb-8">
                                                    @if(!$loop->last)
                                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-neutral-200 dark:bg-neutral-700" aria-hidden="true"></span>
                                                    @endif
                                                    <div class="relative flex items-start gap-3">
                                                        <div>
                                                            <span class="h-8 w-8 rounded-full bg-neutral-400 dark:bg-neutral-600 flex items-center justify-center ring-8 ring-white dark:ring-zinc-800">
                                                                <flux:icon name="{{ $icon }}" class="w-4 h-4 text-white" />
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
                                                                <time 
                                                                    datetime="{{ $activity->created_at->toISOString() }}"
                                                                    title="{{ $activity->created_at->format('M j, Y g:i A') }}"
                                                                >
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
                                <div class="mt-4">
                                    {{ $activities->links() }}
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
                                        @php
                                            $approvalType = class_basename($approval::class);
                                            $statusBadgeColor = match($approval->status ?? 'pending') {
                                                'pending' => 'yellow',
                                                'submitted' => 'blue',
                                                'approved' => 'green',
                                                default => 'gray',
                                            };
                                        @endphp
                                        <div class="flex items-center justify-between gap-3 rounded-lg border border-yellow-100 bg-yellow-50 p-3 dark:border-yellow-900/30 dark:bg-yellow-900/20">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <flux:text class="text-sm font-medium text-neutral-900 dark:text-white">
                                                        {{ $approval->member->full_name ?? 'Unknown Member' }}
                                                    </flux:text>
                                                    <flux:badge size="sm" color="{{ $statusBadgeColor }}">
                                                        {{ ucfirst($approval->status ?? 'pending') }}
                                                    </flux:badge>
                                                </div>
                                                <flux:text class="text-xs text-neutral-600 dark:text-neutral-400">
                                                    {{ $approvalType === 'Loan' ? 'Loan' : ($approvalType === 'HealthClaim' ? 'Health Claim' : $approvalType) }} - ₦{{ number_format($approval->amount ?? $approval->covered_amount ?? 0, 2) }}
                                                </flux:text>
                                            </div>
                                            <flux:button 
                                                size="sm" 
                                                variant="primary" 
                                                :href="$approvalType === 'Loan' ? route('loans.show', $approval->id) : ($approvalType === 'HealthClaim' ? '#' : '#')" 
                                                wire:navigate
                                                aria-label="Review {{ $approvalType }} for {{ $approval->member->full_name ?? 'member' }}"
                                            >
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
                                            aria-label="{{ $action['title'] }}"
                                        >
                                            <flux:icon name="{{ $action['icon'] }}" class="size-4" aria-hidden="true" />
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
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800"
                     x-data="{
                         charts: {},
                         initCharts() {
                             @foreach($dashboardData['charts'] as $chartName => $chartData)
                                 this.initChart('{{ $chartName }}', @js($chartData));
                             @endforeach
                         },
                         initChart(chartName, chartData) {
                             const ctx = document.getElementById('chart-' + chartName);
                             if (!ctx || !window.Chart) return;

                             // Destroy existing chart if it exists
                             if (this.charts[chartName]) {
                                 this.charts[chartName].destroy();
                             }

                             // Determine chart type based on data
                             const chartType = this.getChartType(chartName, chartData);
                             const config = this.getChartConfig(chartName, chartType, chartData);

                             this.charts[chartName] = new window.Chart(ctx, config);
                         },
                         getChartType(chartName, chartData) {
                             // Determine chart type based on chart name
                             if (chartName.includes('distribution') || chartName.includes('status') || chartName.includes('type') || chartName.includes('flow')) {
                                 return 'doughnut';
                             }
                             return 'line';
                         },
                         getChartConfig(chartName, chartType, chartData) {
                             const isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                             const textColor = isDark ? '#e5e7eb' : '#374151';
                             const gridColor = isDark ? '#374151' : '#e5e7eb';

                             if (chartType === 'doughnut') {
                                 return {
                                     type: 'doughnut',
                                     data: {
                                         labels: chartData.labels || [],
                                         datasets: [{
                                             data: chartData.data || [],
                                             backgroundColor: [
                                                 'rgb(59, 130, 246)',
                                                 'rgb(34, 197, 94)',
                                                 'rgb(234, 179, 8)',
                                                 'rgb(239, 68, 68)',
                                                 'rgb(168, 85, 247)',
                                                 'rgb(236, 72, 153)',
                                             ],
                                         }],
                                     },
                                     options: {
                                         responsive: true,
                                         maintainAspectRatio: false,
                                         plugins: {
                                             legend: {
                                                 position: 'bottom',
                                                 labels: {
                                                     color: textColor,
                                                     padding: 15,
                                                 },
                                             },
                                         },
                                     },
                                 };
                             }

                             return {
                                 type: 'line',
                                 data: {
                                     labels: chartData.labels || [],
                                     datasets: [{
                                         label: chartName.replace(/_/g, ' '),
                                         data: chartData.data || [],
                                         borderColor: 'rgb(59, 130, 246)',
                                         backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                         tension: 0.4,
                                         fill: true,
                                     }],
                                 },
                                 options: {
                                     responsive: true,
                                     maintainAspectRatio: false,
                                     plugins: {
                                         legend: {
                                             display: false,
                                         },
                                         tooltip: {
                                             mode: 'index',
                                             intersect: false,
                                         },
                                     },
                                     scales: {
                                         y: {
                                             beginAtZero: true,
                                             ticks: {
                                                 color: textColor,
                                             },
                                             grid: {
                                                 color: gridColor,
                                             },
                                         },
                                         x: {
                                             ticks: {
                                                 color: textColor,
                                             },
                                             grid: {
                                                 color: gridColor,
                                             },
                                         },
                                     },
                                 },
                             };
                         }
                     }"
                     x-init="
                         $watch('$wire.dashboardData', () => {
                             setTimeout(() => initCharts(), 100);
                         });
                         setTimeout(() => initCharts(), 100);
                     ">
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
                                    <div class="relative h-64" role="img" aria-label="Chart showing {{ str_replace('_', ' ', $chartName) }} data">
                                        <canvas id="chart-{{ $chartName }}" aria-label="{{ str_replace('_', ' ', $chartName) }} chart"></canvas>
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
