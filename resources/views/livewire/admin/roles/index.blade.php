<?php

use App\Models\Role;
use App\Models\Permission;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';
    public array $permissions = [];

    public function mount(): void
    {
        $this->permissions = Permission::orderBy('name')->get()->toArray();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteRole(Role $role): void
    {
        // Check if role has users
        if ($role->users()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete role that is assigned to users.',
            ]);
            return;
        }

        $role->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Role deleted successfully.',
        ]);
    }

    public function with(): array
    {
        $query = Role::query()
            ->withCount('users')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
            });

        return [
            'roles' => $query->paginate(15),
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="leading-tight text-xl font-semibold text-gray-900 dark:text-white">{{ __('Role Management') }}</h2>
        <flux:button :href="route('admin.roles.create')" primary wire:navigate class="gap-2">
            <flux:icon name="plus-circle" class="size-4" />
            {{ __('Create New Role') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Search -->
            <div class="mb-6">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search roles...') }}" 
                    icon="magnifying-glass"
                />
            </div>

            <!-- Roles Table -->
            @if($roles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Role') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Description') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Users') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Permissions') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($roles as $role)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                                                    <span class="text-sm font-medium text-blue-700 dark:text-blue-200">
                                                        {{ substr($role->name, 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                                    {{ $role->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $role->description ?? 'No description' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button 
                                                :href="route('admin.roles.show', $role)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('View') }}
                                            </flux:button>
                                            
                                            <flux:button 
                                                :href="route('admin.roles.edit', $role)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('Edit') }}
                                            </flux:button>
                                            
                                            <flux:button 
                                                wire:click="deleteRole({{ $role->id }})"
                                                size="sm" 
                                                variant="danger"
                                                wire:confirm="Are you sure you want to delete this role? This action cannot be undone."
                                            >
                                                {{ __('Delete') }}
                                            </flux:button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $roles->links() }}
                </div>
            @else
                <x-empty-state 
                    title="{{ __('No Roles Found') }}" 
                    description="{{ __('No roles match your current search criteria.') }}"
                />
            @endif
        </div>
    </div>
</div>
