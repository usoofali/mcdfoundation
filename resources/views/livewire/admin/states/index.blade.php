<?php

use App\Models\State;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'States Management'])] class extends Component {
    use WithPagination;

    public $search = '';
    public $name = '';
    public $editingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255|unique:states,name,' . ($this->editingId ?? 'NULL'),
        ]);

        if ($this->editingId) {
            $state = State::findOrFail($this->editingId);
            $state->update($validated);
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'State updated successfully.',
            ]);
        } else {
            State::create($validated);
            session()->flash('success', 'State created successfully.');
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'State created successfully.',
            ]);
        }

        $this->reset(['name', 'editingId']);
    }

    public function edit($stateId): void
    {
        $state = State::findOrFail($stateId);
        $this->editingId = $state->id;
        $this->name = $state->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['name', 'editingId']);
    }

    public function delete($stateId): void
    {
        $state = State::findOrFail($stateId);

        if ($state->lgas()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete state with existing LGAs.',
            ]);
            return;
        }

        if ($state->users()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete state with existing users.',
            ]);
            return;
        }

        $state->delete();
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'State deleted successfully.',
        ]);
    }

    public function with(): array
    {
        $query = State::query()
            ->withCount(['lgas', 'users'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            });

        return [
            'states' => $query->orderBy('name')->paginate(15),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                States Management
            </flux:heading>
            <flux:text class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                Manage Nigerian states in the system
            </flux:text>
        </div>

        <!-- Add/Edit Form -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <flux:heading size="sm" class="mb-4">{{ $editingId ? 'Edit State' : 'Add New State' }}</flux:heading>
            <form wire:submit="save" class="flex gap-4 items-end">
                <div class="flex-1">
                    <flux:input wire:model="name" label="State Name" placeholder="Enter state name" required />
                </div>
                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? 'Update' : 'Add' }} State
                    </flux:button>
                    @if($editingId)
                        <flux:button type="button" variant="ghost" wire:click="cancelEdit">
                            Cancel
                        </flux:button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Search and List -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="mb-6">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search states..."
                    icon="magnifying-glass" />
            </div>

            @if($states->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    State Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    LGAs
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
                            @foreach($states as $state)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                            {{ $state->name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $state->lgas_count }} LGAs
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $state->users_count }} users
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <flux:button size="sm" variant="outline" wire:click="edit({{ $state->id }})">
                                                Edit
                                            </flux:button>

                                            <flux:modal.trigger name="confirm-delete-state-{{ $state->id }}">
                                                <flux:button size="sm" variant="danger"
                                                    wire:click="$dispatch('open-modal', 'confirm-delete-state-{{ $state->id }}')">
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
                    {{ $states->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <flux:heading size="sm" class="font-medium text-neutral-900 dark:text-white">
                        No States Found
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        No states match your search criteria.
                    </flux:text>
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modals -->
    @foreach($states as $state)
        <flux:modal name="confirm-delete-state-{{ $state->id }}" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $state->name]) }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:modal.close>
                        <flux:button variant="danger" wire:click="delete({{ $state->id }})">
                            {{ __('Delete') }}
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endforeach
</div>