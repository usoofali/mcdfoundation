<?php

use App\Models\Lga;
use App\Models\State;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'LGAs Management'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $stateFilter = 'all';
    public $name = '';
    public $state_id = '';
    public $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStateFilter(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
        ]);

        if ($this->editingId) {
            $lga = Lga::findOrFail($this->editingId);
            $lga->update($validated);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'LGA updated successfully.',
               ]);
        } else {
            Lga::create($validated);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'LGA created successfully.',
            ]);
        }

        $this->reset(['name', 'state_id', 'editingId']);
    }

    public function edit($lgaId): void
    {
        $lga = Lga::findOrFail($lgaId);
        $this->editingId = $lga->id;
        $this->name = $lga->name;
        $this->state_id = $lga->state_id;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'state_id', 'editingId']);
    }

    public function delete($lgaId): void
    {
        $lga = Lga::findOrFail($lgaId);

        if ($lga->users()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete LGA that is assigned to users.',
            ]);
            return;
        }

        $lga->delete();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'LGA deleted successfully.',
        ]);
    }

    public function with(): array
    {
        $query = Lga::query()
            ->with('state')
            ->withCount('users')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->stateFilter !== 'all', function ($query) {
                $query->where('state_id', $this->stateFilter);
            });

        return [
            'lgas' => $query->orderBy('name')->paginate(15),
            'states' => State::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                LGAs Management
            </flux:heading>
            <flux:text class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                Manage Local Government Areas in the system
            </flux:text>
        </div>

        <!-- Add/Edit Form -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <flux:heading size="sm" class="mb-4">{{ $editingId ? 'Edit LGA' : 'Add New LGA' }}</flux:heading>
            <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:select wire:model="state_id" label="State" required>
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}">{{ $state->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <div>
                    <flux:input wire:model="name" label="LGA Name" placeholder="Enter LGA name" required />
                </div>
                <div class="flex items-end gap-2">
                    <flux:button type="submit" variant="primary" class="w-full">
                        {{ $editingId ? 'Update' : 'Add' }} LGA
                    </flux:button>
                    @if($editingId)
                        <flux:button type="button" variant="ghost" wire:click="cancelEdit">
                            Cancel
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Search and Filter -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search LGAs..."
                    icon="magnifying-glass" />
                <flux:select wire:model.live="stateFilter">
                    <option value="all">All States</option>
                    @foreach($states as $state)
                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            @if($lgas->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    LGA Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    State
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    Users
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($lgas as $lga)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                            {{ $lga->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $lga->state->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $lga->users_count }} users
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <flux:button size="sm" variant="outline" wire:click="edit({{ $lga->id }})">
                                                Edit
                                            </flux:button>

                                            <flux:modal.trigger name="confirm-delete-lga-{{ $lga->id }}">
                                                <flux:button size="sm" variant="danger"
                                                    wire:click="$dispatch('open-modal', 'confirm-delete-lga-{{ $lga->id }}')">
                                                    Delete
                                                </flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $lgas->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                        No LGAs Found
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        No LGAs match your search criteria.
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modals -->
    @foreach($lgas as $lga)
        <flux:modal name="confirm-delete-lga-{{ $lga->id }}" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $lga->name]) }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:modal.close>
                        <flux:button variant="danger" wire:click="delete({{ $lga->id }})">
                            {{ __('Delete') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endforeach
</div>