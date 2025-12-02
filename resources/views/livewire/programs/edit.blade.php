<?php

use App\Models\Program;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Edit Program'])] class extends Component {
    use AuthorizesRequests;

    public Program $program;
    public $name = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;
    public $capacity = null;
    public $min_contributions = 0;
    public $min_age = null;
    public $max_age = null;

    public function mount(Program $program): void
    {
        $this->authorize('update', $program);
        $this->program = $program;

        $this->name = $program->name;
        $this->description = $program->description ?? '';
        $this->start_date = $program->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $program->end_date?->format('Y-m-d') ?? '';
        $this->is_active = $program->is_active;
        $this->capacity = $program->capacity;

        $rules = $program->eligibility_rules ?? [];
        $this->min_contributions = $rules['min_contributions'] ?? 0;
        $this->min_age = $rules['min_age'] ?? null;
        $this->max_age = $rules['max_age'] ?? null;
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'capacity' => 'nullable|integer|min:1',
            'min_contributions' => 'required|integer|min:0',
            'min_age' => 'nullable|integer|min:1|max:150',
            'max_age' => 'nullable|integer|min:1|max:150|gte:min_age',
        ]);

        $eligibilityRules = array_filter([
            'min_contributions' => $this->min_contributions,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
        ], fn($value) => $value !== null && $value !== '');

        $this->program->update([
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
            'capacity' => $this->capacity,
            'eligibility_rules' => $eligibilityRules,
        ]);

        session()->flash('success', 'Program updated successfully.');
        $this->redirect(route('programs.show', $this->program), navigate: true);
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Edit Program
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Update {{ $program->name }}
                </flux:text>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('programs.show', $program) }}" wire:navigate>
                    Back to Program
                </flux:button>
            </div>
        </div>
    </div>

    @if($program->enrollments()->exists())
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-900/20">
            <div class="flex items-start gap-3">
                <flux:icon name="exclamation-triangle" class="size-5 text-yellow-600 dark:text-yellow-400" />
                <div>
                    <flux:heading size="sm" class="font-medium text-yellow-900 dark:text-yellow-100">
                        This program has active enrollments
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-yellow-700 dark:text-yellow-300">
                        Changing eligibility rules may affect existing members' status.
                    </flux:text>
                </div>
            </div>
        </div>
    @endif

    <!-- Form (same as create) -->
    <form wire:submit="update">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                        Basic Information
                    </flux:heading>
                    
                    <div class="space-y-4">
                        <div>
                            <flux:input wire:model="name" label="Program Name" required />
                            @error('name') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="description" rows="4" />
                            @error('description') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:input wire:model="start_date" type="date" label="Start Date" />
                                @error('start_date') <flux:error>{{ $message }}</flux:error> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="end_date" type="date" label="End Date" />
                                @error('end_date') <flux:error>{{ $message }}</flux:error> @enderror
                            </div>
                        </div>

                        <div>
                            <flux:input wire:model="capacity" type="number" min="1" label="Capacity" 
                                placeholder="Leave empty for unlimited" />
                            <flux:text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Maximum number of members that can enroll
                            </flux:text>
                            @error('capacity') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div>
                            <flux:checkbox wire:model="is_active" label="Program is active and accepting enrollments" />
                        </div>
                    </div>
                </div>

                <!-- Eligibility Requirements -->
                <div class="border-t border-neutral-200 dark:border-neutral-700 pt-6">
                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                        Eligibility Requirements
                    </flux:heading>

                    <div class="space-y-4">
                        <div>
                            <flux:input wire:model="min_contributions" type="number" min="0" 
                                label="Minimum Contributions Required" required />
                            @error('min_contributions') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <flux:input wire:model="min_age" type="number" min="1" max="150" 
                                    label="Minimum Age" placeholder="Optional" />
                                @error('min_age') <flux:error>{{ $message }}</flux:error> @enderror
                            </div>
                            <div>
                                <flux:input wire:model="max_age" type="number" min="1" max="150" 
                                    label="Maximum Age" placeholder="Optional" />
                                @error('max_age') <flux:error>{{ $message }}</flux:error> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 border-t border-neutral-200 dark:border-neutral-700 pt-6">
                    <flux:button variant="ghost" href="{{ route('programs.show', $program) }}" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Update Program
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
