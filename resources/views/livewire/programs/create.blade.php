<?php

use App\Models\Program;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create Program'])] class extends Component {
    use AuthorizesRequests;

    public $name = '';
    public $description = '';
    public $start_date = '';
    public $end_date = '';
    public $is_active = true;
    public $capacity = null;
    public $min_contributions = 0;
    public $min_age = null;
    public $max_age = null;

    public function mount(): void
    {
        $this->authorize('create', Program::class);
    }

    public function save(): void
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

        $program = Program::create([
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $this->start_date ?: null,
            'end_date' => $this->end_date ?: null,
            'is_active' => $this->is_active,
            'capacity' => $this->capacity,
            'eligibility_rules' => $eligibilityRules,
            'created_by' => auth()->id(),
        ]);

        session()->flash('success', 'Program created successfully.');
        $this->redirect(route('programs.show', $program), navigate: true);
    }
}; ?>

<div class="space-y-6 p-6">
    <!-- Header -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Create New Program
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Create a new vocational training program for members
                </flux:text>
            </div>
            <div>
                <flux:button variant="ghost" href="{{ route('programs.index') }}" wire:navigate>
                    Back to Programs
                </flux:button>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form wire:submit="save">
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <flux:heading size="sm" class="mb-4 font-medium text-neutral-900 dark:text-white">
                        Basic Information
                    </flux:heading>
                    
                    <div class="space-y-4">
                        <div>
                            <flux:input wire:model="name" label="Program Name" placeholder="e.g., Tailoring & Fashion Design" required />
                            @error('name') <flux:error>{{ $message }}</flux:error> @enderror
                        </div>

                        <div>
                            <flux:label>Description</flux:label>
                            <flux:textarea wire:model="description" rows="4" 
                                placeholder="Describe the program objectives, content, and expected outcomes..." />
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
                                Maximum number of members that can enroll (leave empty for unlimited capacity)
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
                            <flux:text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                                Number of paid contributions a member must have (0 = no requirement)
                            </flux:text>
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
                    <flux:button variant="ghost" href="{{ route('programs.index') }}" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Create Program
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>
