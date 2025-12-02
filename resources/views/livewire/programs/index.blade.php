<?php

use App\Models\Program;
use App\Services\ProgramService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Programs'])] class extends Component {
    use AuthorizesRequests, WithPagination;

    public $search = '';
    public $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Program::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete($programId): void
    {
        $program = Program::findOrFail($programId);
        $this->authorize('delete', $program);

        try {
            $programService = app(ProgramService::class);
            $programService->deleteProgram($program);

            session()->flash('success', 'Program deleted successfully.');

            // Reset pagination if needed
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function getProgramsProperty()
    {
        $programService = app(ProgramService::class);

        $filters = [];
        if ($this->search) {
            $filters['search'] = $this->search;
        }
        if ($this->statusFilter !== 'all') {
            $filters['status'] = $this->statusFilter;
        }

        return $programService->getPrograms($filters);
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Vocational Programs
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Manage vocational training programs and member enrollments
                </flux:text>
            </div>
            @can('create', Program::class)
                <div>
                    <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('programs.create') }}"
                        class="gap-2">
                        Create Program
                    </flux:button>
                </div>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <flux:input wire:model.live="search" placeholder="Search programs..." icon="magnifying-glass" />
            </div>
            <div>
                <flux:select wire:model.live="statusFilter">
                    <option value="all">All Programs</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Programs Table -->
    <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
        @if($this->programs->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Program Name
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Duration
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Enrollment
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                        @foreach($this->programs as $program)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $program->name }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ Str::limit($program->description, 60) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    <div>{{ $program->start_date?->format('M d, Y') ?? 'TBD' }}</div>
                                    <div class="text-xs">to {{ $program->end_date?->format('M d, Y') ?? 'TBD' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                    <div>{{ $program->enrolled_count }} enrolled</div>
                                    @if($program->capacity)
                                        <div class="text-xs">{{ $program->available_slots }} / {{ $program->capacity }} slots left
                                        </div>
                                    @else
                                        <div class="text-xs">Unlimited capacity</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                                                @if($program->is_active) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                                                                @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200
                                                                                @endif">
                                        {{ $program->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <flux:button size="sm" href="{{ route('programs.show', $program) }}" wire:navigate>
                                            View
                                        </flux:button>
                                        @can('update', $program)
                                            <flux:button size="sm" href="{{ route('programs.edit', $program) }}" wire:navigate>
                                                Edit
                                            </flux:button>
                                        @endcan
                                        @can('delete', $program)
                                            <flux:modal.trigger name="confirm-delete-program-{{ $program->id }}">
                                                <flux:button variant="danger" size="sm"
                                                    wire:click="$dispatch('open-modal', 'confirm-delete-program-{{ $program->id }}')">
                                                    Delete
                                                </flux:button>
                                            </flux:modal.trigger>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-neutral-200 dark:border-neutral-700">
                {{ $this->programs->links() }}
            </div>
        @else
            <div class="py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                    No programs found
                </flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    Get started by creating a new vocational program.
                </flux:text>
                @can('create', Program::class)
                    <div class="mt-6">
                        <flux:button variant="primary" icon="plus" variant="primary" href="{{ route('programs.create') }}"
                            class="gap-2">
                            Create Program
                        </flux:button>
                    </div>
                @endcan
            </div>
        @endif
    </div>

    <!-- Delete Confirmation Modals -->
    @foreach($this->programs as $program)
        @can('delete', $program)
            <flux:modal name="confirm-delete-program-{{ $program->id }}" focusable class="max-w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $program->title]) }}
                        </flux:subheading>
                    </div>

                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:modal.close>
                            <flux:button variant="danger" wire:click="delete({{ $program->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        @endcan
    @endforeach
</div>