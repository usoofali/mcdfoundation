<?php

use App\Models\ExpectedContribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Overdue Contributions'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $state = '';
    public $lga = '';

    public function with(): array
    {
        $query = ExpectedContribution::overdue()
            ->with(['member.state', 'member.lga', 'contributionPlan'])
            ->whereHas('member', function ($q) {
                $q->forAuthUserLocation();

                if ($this->search) {
                    $q->where(function ($query) {
                        $query->where('full_name', 'like', "%{$this->search}%")
                            ->orWhere('registration_no', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%");
                    });
                }

                if ($this->state) {
                    $q->where('state_id', $this->state);
                }

                if ($this->lga) {
                    $q->where('lga_id', $this->lga);
                }
            });

        $overdueContributions = $query->orderBy('due_date', 'asc')->paginate(20);

        $stats = [
            'total_count' => ExpectedContribution::overdue()
                ->whereHas('member', fn($q) => $q->forAuthUserLocation())
                ->count(),
            'total_amount' => ExpectedContribution::overdue()
                ->whereHas('member', fn($q) => $q->forAuthUserLocation())
                ->sum('expected_amount'),
            'total_fines' => ExpectedContribution::overdue()
                ->whereHas('member', fn($q) => $q->forAuthUserLocation())
                ->sum('fine_amount'),
        ];

        return [
            'overdueContributions' => $overdueContributions,
            'stats' => $stats,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Overdue Contributions</flux:heading>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
            <div class="text-sm text-red-700 dark:text-red-300">Total Overdue</div>
            <div class="mt-1 text-2xl font-semibold text-red-900 dark:text-red-100">{{ $stats['total_count'] }} members
            </div>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
            <div class="text-sm text-red-700 dark:text-red-300">Total Amount</div>
            <div class="mt-1 text-2xl font-semibold text-red-900 dark:text-red-100">
                ₦{{ number_format($stats['total_amount'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-950">
            <div class="text-sm text-orange-700 dark:text-orange-300">Total Fines</div>
            <div class="mt-1 text-2xl font-semibold text-orange-900 dark:text-orange-100">
                ₦{{ number_format($stats['total_fines'], 2) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name, phone, or reg no..." />
            <flux:select wire:model.live="state" placeholder="All States">
                <option value="">All States</option>
                <!-- Add state options dynamically -->
            </flux:select>
            <flux:select wire:model.live="lga" placeholder="All LGAs">
                <option value="">All LGAs</option>
                <!-- Add LGA options dynamically -->
            </flux:select>
        </div>
    </div>

    <!-- Overdue List -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg">Overdue Payments</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Member</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Contact</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Plan</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Due Date</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Days Overdue</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Amount</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Fine</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Total</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($overdueContributions as $expected)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-neutral-900 dark:text-white">{{ $expected->member->full_name }}
                                </div>
                                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                    {{ $expected->member->registration_no }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900 dark:text-white">📱 {{ $expected->member->phone }}
                                </div>
                                @if($expected->member->email)
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">📧
                                        {{ $expected->member->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $expected->contributionPlan->label }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                {{ $expected->due_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                {{ $expected->days_overdue }} days
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                ₦{{ number_format($expected->expected_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">
                                ₦{{ number_format($expected->fine_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-neutral-900 dark:text-white">
                                ₦{{ number_format($expected->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2">
                                    <flux:button size="sm" href="{{ route('admin.members.show', $expected->member) }}">
                                        View
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400">
                                No overdue contributions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
            {{ $overdueContributions->links() }}
        </div>
    </div>
</div>