<?php

use App\Models\Member;
use App\Models\Dependent;
use App\Services\DependentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Member $member;
    public $dependents;
    public $showModal = false;
    public $editingDependent = null;
    public $form = [
        'name' => '',
        'nin' => '',
        'date_of_birth' => '',
        'relationship' => '',
        'document' => null,
        'notes' => '',
    ];

    public function mount(Member $member): void
    {
        $this->member = $member;
        
        // Check authorization
        if (!auth()->user()->can('view', $member)) {
            abort(403, 'You do not have permission to view this member\'s dependents.');
        }
        
        $this->loadDependents();
    }

    public function loadDependents(): void
    {
        $this->dependents = $this->member->dependents()
            ->orderBy('relationship')
            ->orderBy('name')
            ->get();
    }

    public function showCreateModal(): void
    {
        if (!auth()->user()->can('create', Dependent::class)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to create dependents.',
            ]);
            return;
        }

        $this->resetForm();
        $this->showModal = true;
        $this->editingDependent = null;
    }

    public function showEditModal(Dependent $dependent): void
    {
        if (!auth()->user()->can('update', $dependent)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to edit this dependent.',
            ]);
            return;
        }

        $this->editingDependent = $dependent;
        $this->form = [
            'name' => $dependent->name,
            'nin' => $dependent->nin,
            'date_of_birth' => $dependent->date_of_birth->format('Y-m-d'),
            'relationship' => $dependent->relationship,
            'document' => null,
            'notes' => $dependent->notes ?? '',
        ];
        $this->showModal = true;
    }

    public function save(): void
    {
        $uniqueNinRule = Rule::unique('dependents', 'nin')
            ->whereNull('deleted_at')
            ->ignore($this->editingDependent?->id);

        $this->validate([
            'form.name' => 'required|string|max:150',
            'form.nin' => ['required', 'string', 'size:11', 'regex:/^[0-9]{11}$/', $uniqueNinRule],
            'form.date_of_birth' => 'required|date|before:today',
            'form.relationship' => 'required|in:spouse,child,parent,sibling,other',
            'form.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'form.notes' => 'nullable|string|max:1000',
        ]);

        $dependentService = app(DependentService::class);

        if ($this->editingDependent) {
            $dependentService->updateDependent($this->editingDependent, $this->form);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Dependent updated successfully.',
            ]);
        } else {
            $dependentService->createDependent($this->member, $this->form);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Dependent added successfully.',
            ]);
        }

        $this->loadDependents();
        $this->closeModal();
    }

    public function delete(Dependent $dependent): void
    {
        if (!auth()->user()->can('delete', $dependent)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You do not have permission to delete this dependent.',
            ]);
            return;
        }

        $dependentService = app(DependentService::class);
        $dependentService->deleteDependent($dependent);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Dependent deleted successfully.',
        ]);
        $this->loadDependents();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->editingDependent = null;
    }

    public function resetForm(): void
    {
        $this->form = [
            'name' => '',
            'nin' => '',
            'date_of_birth' => '',
            'relationship' => '',
            'document' => null,
            'notes' => '',
        ];
    }

    public function getRelationshipOptionsProperty()
    {
        return [
            'spouse' => 'Spouse',
            'child' => 'Child',
            'parent' => 'Parent',
            'sibling' => 'Sibling',
            'other' => 'Other',
        ];
    }

    public function getDependentStatsProperty()
    {
        $dependentService = app(DependentService::class);
        return $dependentService->getDependentStats($this->member);
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header with Stats -->
            <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                        Dependents
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Manage family members for {{ $member->full_name }}
                    </flux:text>
                </div>
                <flux:button icon="user-plus" variant="primary" wire:click="showCreateModal">
                    
                    Add Dependent
                </flux:button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-indigo-100 p-2 sm:p-3 dark:bg-indigo-900/20">
                            <flux:icon name="users" class="size-5 sm:size-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Dependents
                            </flux:text>
                            <flux:heading size="lg" class="mt-1 font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                {{ $this->dependentStats['total'] }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-green-100 p-2 sm:p-3 dark:bg-green-900/20">
                            <flux:icon name="shield-check" class="size-5 sm:size-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Eligible
                            </flux:text>
                            <flux:heading size="lg" class="mt-1 font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                {{ $this->dependentStats['eligible'] }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-purple-100 p-2 sm:p-3 dark:bg-purple-900/20">
                            <flux:icon name="heart" class="size-5 sm:size-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Children
                            </flux:text>
                            <flux:heading size="lg" class="mt-1 font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                {{ $this->dependentStats['children'] }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-orange-100 p-2 sm:p-3 dark:bg-orange-900/20">
                            <flux:icon name="user-group" class="size-5 sm:size-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Spouses
                            </flux:text>
                            <flux:heading size="lg" class="mt-1 font-semibold text-base sm:text-lg text-neutral-900 dark:text-white">
                                {{ $this->dependentStats['spouses'] }}
                            </flux:heading>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dependents List -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-800">
            @if($dependents->count() > 0)
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">NIN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Age</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Relationship</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Eligible</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Document</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($dependents as $dependent)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">{{ $dependent->name }}</div>
                                        @if($dependent->notes)
                                            <div class="text-sm text-neutral-500 dark:text-neutral-400">{{ Str::limit($dependent->notes, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $dependent->nin }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $dependent->age }} years
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $dependent->relationship_label }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $dependent->eligible ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200' }}">
                                            {{ $dependent->eligible ? 'Eligible' : 'Not Eligible' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        @if($dependent->document_path)
                                            <a href="{{ Storage::url($dependent->document_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                                                View Document
                                            </a>
                                        @else
                                            <span class="text-neutral-400 dark:text-neutral-500">No document</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button variant="ghost" size="sm" wire:click="showEditModal({{ $dependent->id }})">
                                                Edit
                                            </flux:button>
                                            <flux:button 
                                                variant="ghost" 
                                                size="sm" 
                                                wire:click="delete({{ $dependent->id }})"
                                                wire:confirm="Are you sure you want to delete this dependent?"
                                            >
                                                Delete
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-neutral-400 dark:text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                        No dependents
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        Get started by adding a family member.
                    </flux:text>
                    <div class="mt-6">
                        <flux:button  icon="user-plus" variant="primary" wire:click="showCreateModal" class="gap-2">
                            
                            Add Dependent
                        </flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 z-40 bg-neutral-900/60 transition-opacity" wire:click="closeModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative z-50 inline-block w-full max-w-lg transform overflow-hidden rounded-2xl border border-neutral-200 bg-white text-left shadow-xl transition-all dark:border-neutral-700 dark:bg-neutral-800 sm:my-8 sm:align-middle">
                    <form wire:submit="save">
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full">
                                    <flux:heading size="md" class="mb-4 font-semibold text-neutral-900 dark:text-white">
                                        {{ $editingDependent ? 'Edit Dependent' : 'Add Dependent' }}
                                    </flux:heading>
                                    
                                    <div class="space-y-4">
                                        <flux:input 
                                            wire:model="form.name" 
                                            label="Full Name" 
                                            required 
                                            autofocus
                                        />

                                        <flux:input
                                            wire:model="form.nin"
                                            label="National ID Number (NIN)"
                                            placeholder="11-digit NIN"
                                            required
                                        />
                                        
                                        <flux:input 
                                            wire:model="form.date_of_birth" 
                                            type="date" 
                                            label="Date of Birth" 
                                            required
                                        />
                                        
                                        <flux:select
                                            wire:model="form.relationship"
                                            label="Relationship"
                                            placeholder="Select relationship"
                                            required
                                        >
                                            @foreach($this->relationshipOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </flux:select>
                                        
                                        <flux:input 
                                            wire:model="form.document" 
                                            type="file" 
                                            label="Document (Optional)"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                        />
                                        
                                        <flux:textarea 
                                            wire:model="form.notes" 
                                            label="Notes (Optional)"
                                            rows="3"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-neutral-200 bg-neutral-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6 dark:border-neutral-700 dark:bg-neutral-900">
                            <flux:button type="submit" variant="primary" class="w-full gap-2 sm:w-auto">
                                {{ $editingDependent ? 'Update' : 'Add' }} Dependent
                            </flux:button>
                            <flux:button type="button" variant="outline" wire:click="closeModal" class="w-full sm:w-auto">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
