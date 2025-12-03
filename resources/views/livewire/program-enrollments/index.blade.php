<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Program Enrollments'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $programFilter = 'all';
    public $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProgramFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function markAsCompleted($enrollmentId): void
    {
        $enrollment = ProgramEnrollment::findOrFail($enrollmentId);
        $enrollment->markAsCompleted();
        session()->flash('success', 'Enrollment marked as completed successfully.');
    }

    public function issueCertificate($enrollmentId): void
    {
        $enrollment = ProgramEnrollment::findOrFail($enrollmentId);
        $enrollment->issueCertificate();
        session()->flash('success', 'Certificate issued successfully.');
    }

    public function markAsDropped($enrollmentId): void
    {
        $enrollment = ProgramEnrollment::findOrFail($enrollmentId);
        $enrollment->update(['status' => 'dropped']);
        session()->flash('success', 'Enrollment marked as dropped.');
    }

    public function with(): array
    {
        $query = ProgramEnrollment::query()
            ->with(['member', 'program'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('registration_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->programFilter !== 'all', function ($query) {
                $query->where('program_id', $this->programFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest('enrolled_at');

        return [
            'enrollments' => $query->paginate(15),
            'programs' => Program::active()->orderBy('name')->get(),
            'stats' => [
                'total' => ProgramEnrollment::count(),
                'enrolled' => ProgramEnrollment::where('status', 'enrolled')->count(),
                'completed' => ProgramEnrollment::where('status', 'completed')->count(),
                'dropped' => ProgramEnrollment::where('status', 'dropped')->count(),
            ],
        ];
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Program Enrollments
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Manage member enrollments in vocational programs
                </flux:text>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['total'] }}</div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Enrollments</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['enrolled'] }}</div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Currently Enrolled</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Completed</div>
        </div>
        <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['dropped'] }}</div>
            <div class="text-xs text-neutral-500 dark:text-neutral-400">Dropped</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <flux:input wire:model.live="search" placeholder="Search by member name or ID..."
                    icon="magnifying-glass" />
            </div>
            <div>
                <flux:select wire:model.live="programFilter">
                    <option value="all">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Statuses</option>
                    <option value="enrolled">Enrolled</option>
                    <option value="completed">Completed</option>
                    <option value="dropped">Dropped</option>
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Enrollments Table -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($enrollments->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Member
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Program
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Enrolled Date
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Certificate
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $enrollment->member->full_name }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $enrollment->member->registration_no }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ $enrollment->program->name }}
                                    </div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $enrollment->program->duration_weeks }} weeks
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $enrollment->enrolled_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                        @if($enrollment->status === 'enrolled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                        @elseif($enrollment->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                        @elseif($enrollment->status === 'dropped') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                        @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                        @endif">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($enrollment->certificate_issued)
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Issued
                                        </span>
                                    @else
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400">Not Issued</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($enrollment->status === 'enrolled')
                                            <flux:button variant="ghost" size="sm"
                                                wire:click="markAsCompleted({{ $enrollment->id }})">
                                                Mark Complete
                                            </flux:button>
                                            <flux:button variant="ghost" size="sm"
                                                wire:click="markAsDropped({{ $enrollment->id }})">
                                                Mark Dropped
                                            </flux:button>
                                        @endif

                                        @if($enrollment->status === 'completed' && !$enrollment->certificate_issued)
                                            <flux:button variant="primary" size="sm"
                                                wire:click="issueCertificate({{ $enrollment->id }})">
                                                Issue Certificate
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $enrollments->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                    No Enrollments Found
                </flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    No enrollments match your current search criteria.
                </flux:text>
            </div>
        @endif
    </div>
</div>