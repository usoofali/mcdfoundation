<?php

use App\Models\ExpectedContribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'My Contributions'])] class extends Component {
    use WithPagination;

    public function with(): array
    {
        $member = auth()->user()->member;

        if (!$member) {
            return [
                'expectedContributions' => collect(),
                'stats' => [
                    'total_paid' => 0,
                    'total_pending' => 0,
                    'total_overdue' => 0,
                    'total_fines' => 0,
                ],
            ];
        }

        $expectedContributions = $member->expectedContributions()
            ->with(['contributionPlan', 'actualContribution'])
            ->orderBy('period_start', 'desc')
            ->paginate(15);

        $stats = [
            'total_paid' => $member->expectedContributions()->paid()->sum('expected_amount'),
            'total_pending' => $member->expectedContributions()->pending()->sum('expected_amount'),
            'total_overdue' => $member->expectedContributions()->overdue()->sum('expected_amount'),
            'total_fines' => $member->expectedContributions()->overdue()->sum('fine_amount'),
        ];

        return [
            'expectedContributions' => $expectedContributions,
            'stats' => $stats,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <flux:heading size="xl">My Contributions</flux:heading>
        <flux:button variant="primary" icon="plus" href="{{ route('my.contributions.submit') }}" wire:navigate>
            Submit Contribution
        </flux:button>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-sm text-neutral-600 dark:text-neutral-400">Total Paid</div>
            <div class="mt-1 text-2xl font-semibold text-green-600">₦{{ number_format($stats['total_paid'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-sm text-neutral-600 dark:text-neutral-400">Pending</div>
            <div class="mt-1 text-2xl font-semibold text-yellow-600">₦{{ number_format($stats['total_pending'], 2) }}
            </div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-sm text-neutral-600 dark:text-neutral-400">Overdue</div>
            <div class="mt-1 text-2xl font-semibold text-red-600">₦{{ number_format($stats['total_overdue'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-sm text-neutral-600 dark:text-neutral-400">Total Fines</div>
            <div class="mt-1 text-2xl font-semibold text-orange-600">₦{{ number_format($stats['total_fines'], 2) }}
            </div>
        </div>
    </div>

    <!-- Payment Schedule -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg">Payment Schedule</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Period</th>
                        <th
                            class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Plan</th>
                        <th
                            class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Due Date</th>
                        <th
                            class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Amount</th>
                        <th
                            class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Fine</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Total</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Status</th>
                        <th
                            class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Paid Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($expectedContributions as $expected)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-neutral-900 dark:text-white">
                                {{ $expected->period_start->format('d M, Y') }}
                            </td>
                            <td
                                class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $expected->contributionPlan->label }}
                            </td>
                            <td
                                class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $expected->due_date->format('M d, Y') }}
                                @if($expected->is_overdue)
                                    <span class="text-xs text-red-600 block sm:inline">({{ $expected->days_overdue }}d
                                        overdue)</span>
                                @elseif($expected->status === 'pending' && $expected->days_until_due <= 7)
                                    <span class="text-xs text-orange-600 block sm:inline">({{ $expected->days_until_due }}d
                                        left)</span>
                                @endif
                            </td>
                            <td
                                class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                ₦{{ number_format($expected->expected_amount, 2) }}
                            </td>
                            <td
                                class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm {{ $expected->fine_amount > 0 ? 'text-red-600 font-semibold' : 'text-neutral-500 dark:text-neutral-400' }}">
                                ₦{{ number_format($expected->fine_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-neutral-900 dark:text-white">
                                ₦{{ number_format($expected->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge :color="$expected->status_color" size="sm">
                                    {{ $expected->status_label }}
                                </flux:badge>
                            </td>
                            <td
                                class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                @if($expected->paid_at)
                                    {{ $expected->paid_at->format('M d, Y') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400">
                                No contributions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
            {{ $expectedContributions->links() }}
        </div>
    </div>
</div>