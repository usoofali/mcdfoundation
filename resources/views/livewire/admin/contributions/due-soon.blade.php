<?php

use App\Models\ExpectedContribution;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Due Soon Contributions'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $days = 7;

    public function with(): array
    {
        $query = ExpectedContribution::dueSoon($this->days)
            ->with(['member', 'contributionPlan'])
            ->whereHas('member', function ($q) {
                $q->forAuthUserLocation();

                if ($this->search) {
                    $q->where(function ($query) {
                        $query->where('full_name', 'like', "%{$this->search}%")
                            ->orWhere('registration_no', 'like', "%{$this->search}%");
                    });
                }
            });

        $dueSoonContributions = $query->orderBy('due_date', 'asc')->paginate(20);

        $stats = [
            'total_count' => ExpectedContribution::dueSoon($this->days)
                ->whereHas('member', fn($q) => $q->forAuthUserLocation())
                ->count(),
            'total_amount' => ExpectedContribution::dueSoon($this->days)
                ->whereHas('member', fn($q) => $q->forAuthUserLocation())
                ->sum('expected_amount'),
        ];

        return [
            'dueSoonContributions' => $dueSoonContributions,
            'stats' => $stats,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Contributions Due Soon</flux:heading>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
            <div class="text-sm text-yellow-700 dark:text-yellow-300">Due in {{ $days }} Days</div>
            <div class="mt-1 text-2xl font-semibold text-yellow-900 dark:text-yellow-100">{{ $stats['total_count'] }}
                members</div>
        </div>
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
            <div class="text-sm text-yellow-700 dark:text-yellow-300">Expected Amount</div>
            <div class="mt-1 text-2xl font-semibold text-yellow-900 dark:text-yellow-100">
                ₦{{ number_format($stats['total_amount'], 2) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or reg no..." />
            <flux:select wire:model.live="days">
                <option value="3">Next 3 Days</option>
                <option value="7">Next 7 Days</option>
                <option value="14">Next 14 Days</option>
                <option value="30">Next 30 Days</option>
            </flux:select>
        </div>
    </div>

    <!-- Due Soon List -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
            <flux:heading size="lg">Upcoming Payments</flux:heading>
        </div>

        <div class="overflow-x-auto">
            <flux:table>
                <flux:columns>
                    <flux:column>Member</flux:column>
                    <flux:column>Contact</flux:column>
                    <flux:column>Plan</flux:column>
                    <flux:column>Due Date</flux:column>
                    <flux:column>Days Left</flux:column>
                    <flux:column>Amount</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($dueSoonContributions as $expected)
                        <flux:row>
                            <flux:cell>
                                <div class="font-medium">{{ $expected->member->full_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $expected->member->registration_no }}</div>
                            </flux:cell>
                            <flux:cell>
                                <div class="text-sm">📱 {{ $expected->member->phone }}</div>
                            </flux:cell>
                            <flux:cell>{{ $expected->contributionPlan->label }}</flux:cell>
                            <flux:cell>{{ $expected->due_date->format('M d, Y') }}</flux:cell>
                            <flux:cell class="text-orange-600 font-semibold">{{ $expected->days_until_due }} days
                            </flux:cell>
                            <flux:cell class="font-semibold">₦{{ number_format($expected->expected_amount, 2) }}</flux:cell>
                            <flux:cell>
                                <flux:button size="sm" href="{{ route('admin.members.show', $expected->member) }}">
                                    View
                                </flux:button>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="7" class="text-center text-neutral-500">
                                No contributions due soon
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>
        </div>

        <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
            {{ $dueSoonContributions->links() }}
        </div>
    </div>
</div>