<?php

use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Program Details'])] class extends Component {
    use AuthorizesRequests, WithPagination;

    public Program $program;

    public function mount(Program $program): void
    {
        $this->authorize('view', $program);
        $this->program = $program;
    }

    public function getStatsProperty()
    {
        $programService = app(ProgramService::class);
        return $programService->getProgramStats($this->program);
    }

    public function getEnrollmentsProperty()
    {
        $programService = app(ProgramService::class);
        return $programService->getProgramEnrollments($this->program);
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5 flex-1">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    {{ $program->name }}
                </flux:heading>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($program->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                        @endif">
                        {{ $program->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" href="{{ route('programs.index') }}" wire:navigate>
                    Back to Programs
                </flux:button>
                @can('update', $program)
                    <flux:button variant="primary" href="{{ route('programs.edit', $program) }}" wire:navigate>
                        Edit Program
                    </flux:button>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Program Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Description -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-3 font-medium text-neutral-900 dark:text-white">
                    Description
                </flux:heading>
                <flux:text class="text-neutral-600 dark:text-neutral-400">
                    {{ $program->description ?: 'No description provided.' }}
                </flux:text>
            </div>

            <!-- Program Schedule -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Program Schedule
                </flux:heading>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">Start Date</flux:text>
                        <div class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $program->start_date?->format('M d, Y') ?? 'TBD' }}
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-xs text-neutral-500 dark:text-neutral-400">End Date</flux:text>
                        <div class="mt-1 text-sm text-neutral-900 dark:text-white">
                            {{ $program->end_date?->format('M d, Y') ?? 'TBD' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eligibility Requirements -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Eligibility Requirements
                </flux:heading>
                <div class="space-y-3">
                    @if($program->eligibility_rules && count($program->eligibility_rules) > 0)
                        @if(isset($program->eligibility_rules['min_contributions']))
                            <div class="flex items-center gap-2">
                                <flux:icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                    Minimum {{ $program->eligibility_rules['min_contributions'] }} contribution(s)
                                </flux:text>
                            </div>
                        @endif
                        @if(isset($program->eligibility_rules['min_age']))
                            <div class="flex items-center gap-2">
                                <flux:icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                    Minimum age: {{ $program->eligibility_rules['min_age'] }} years
                                </flux:text>
                            </div>
                        @endif
                        @if(isset($program->eligibility_rules['max_age']))
                            <div class="flex items-center gap-2">
                                <flux:icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                                    Maximum age: {{ $program->eligibility_rules['max_age'] }} years
                                </flux:text>
                            </div>
                        @endif
                    @else
                        <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                            No specific eligibility requirements
                        </flux:text>
                    @endif
                </div>
            </div>

            <!-- Enrolled Members -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
                <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
                    <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                        Enrolled Members
                    </flux:heading>
                </div>

                @if($this->enrollments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                        Member</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                        Enrolled</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @foreach($this->enrollments as $enrollment)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-neutral-900 dark:text-white">
                                                {{ $enrollment->member->full_name }}
                                            </div>
                                            <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                                {{ $enrollment->member->registration_no }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $enrollment->enrolled_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                                @if($enrollment->status === 'enrolled') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                                @elseif($enrollment->status === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                                @endif">
                                                {{ ucfirst($enrollment->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                        {{ $this->enrollments->links() }}
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <flux:text class="text-neutral-500 dark:text-neutral-400">
                            No members enrolled yet
                        </flux:text>
                    </div>
                @endif
            </div>
        </div>

        <!-- Stats Sidebar -->
        <div class="space-y-6">
            <!-- Enrollment Stats -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Enrollment Statistics
                </flux:heading>
                <div class="space-y-4">
                    <div>
                        <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                            {{ $this->stats['total_enrollments'] }}
                        </div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">Total Enrollments</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ $this->stats['enrolled_count'] }}
                        </div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">Currently Enrolled</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ $this->stats['completed_count'] }}
                        </div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">Completed</div>
                    </div>
                    @if($program->capacity)
                        <div class="pt-4 border-t border-neutral-200 dark:border-neutral-700">
                            <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                                {{ $this->stats['available_slots'] }}
                            </div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400">Available Slots (of
                                {{ $this->stats['capacity'] }})
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Info -->
            <div
                class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                    Quick Info
                </flux:heading>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">Created</div>
                        <div class="text-neutral-900 dark:text-white">{{ $program->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($program->creator)
                        <div>
                            <div class="text-xs text-neutral-500 dark:text-neutral-400">Created By</div>
                            <div class="text-neutral-900 dark:text-white">{{ $program->creator->name }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs text-neutral-500 dark:text-neutral-400">Completion Rate</div>
                        <div class="text-neutral-900 dark:text-white">
                            {{ number_format($this->stats['completion_rate'], 1) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>