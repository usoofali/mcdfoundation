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
            <flux:table>
                <flux:columns>
                    <flux:column>Member</flux:column>
                    <flux:column>Contact</flux:column>
                    <flux:column>Plan</flux:column>
                    <flux:column>Due Date</flux:column>
                    <flux:column>Days Overdue</flux:column>
                    <flux:column>Amount</flux:column>
                    <flux:column>Fine</flux:column>
                    <flux:column>Total</flux:column>
                    <flux:column>Actions</flux:column>
                </flux:columns>

                <flux:rows>
                    @forelse($overdueContributions as $expected)
                        <flux:row>
                            <flux:cell>
                                <div class="font-medium">{{ $expected->member->full_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $expected->member->registration_no }}</div>
                            </flux:cell>
                            <flux:cell>
                                <div class="text-sm">📱 {{ $expected->member->phone }}</div>
                                @if($expected->member->email)
                                    <div class="text-xs text-neutral-500">📧 {{ $expected->member->email }}</div>
                                @endif
                            </flux:cell>
                            <flux:cell>{{ $expected->contributionPlan->label }}</flux:cell>
                            <flux:cell>{{ $expected->due_date->format('M d, Y') }}</flux:cell>
                            <flux:cell class="text-red-600 font-semibold">{{ $expected->days_overdue }} days</flux:cell>
                            <flux:cell>₦{{ number_format($expected->expected_amount, 2) }}</flux:cell>
                            <flux:cell class="text-red-600 font-semibold">₦{{ number_format($expected->fine_amount, 2) }}
                            </flux:cell>
                            <flux:cell class="font-bold">₦{{ number_format($expected->total_amount, 2) }}</flux:cell>
                            <flux:cell>
                                <div class="flex gap-2">
                                    <flux:button size="sm" href="{{ route('admin.members.show', $expected->member) }}">
                                        View
                                    </flux:button>
                                </div>
                            </flux:cell>
                        </flux:row>
                    @empty
                        <flux:row>
                            <flux:cell colspan="9" class="text-center text-neutral-500">
                                No overdue contributions found
                            </flux:cell>
                        </flux:row>
                    @endforelse
                </flux:rows>
            </flux:table>
        </div>

        <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
            {{ $overdueContributions->links() }}
        </div>
    </div>
</div>