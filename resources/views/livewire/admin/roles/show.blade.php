<?php

use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Role Details'])] class extends Component
{
    use WithPagination;

    public Role $role;

    public function mount(Role $role): void
    {
        Gate::authorize('view', $role);
        $this->role = $role->load(['permissions']);
    }

    public function getUsersProperty()
    {
        return $this->role->users()->orderBy('name')->paginate(15);
    }

    public function deleteRole(): void
    {
        Gate::authorize('delete', $this->role);

        // Check if role has users
        if ($this->role->users()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete role that is assigned to users.',
            ]);

            return;
        }

        $this->role->delete();

        session()->flash('success', 'Role deleted successfully.');

        $this->redirect(route('admin.roles.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Role Details') }}</h2>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('admin.roles.edit', $role)" variant="outline" wire:navigate>
                {{ __('Edit Role') }}
            </flux:button>
            <flux:button :href="route('admin.roles.index')" variant="outline" wire:navigate>
                {{ __('Back to Roles') }}
            </flux:button>
        </div>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Role Header -->
            <div class="flex items-center space-x-6 mb-8">
                <div class="flex-shrink-0">
                    <div class="h-20 w-20 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                        <span class="text-2xl font-medium text-blue-800 dark:text-blue-200">
                            {{ substr($role->name, 0, 2) }}
                        </span>
                    </div>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role->name }}</h1>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $role->description ?? 'No description provided' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:modal.trigger name="confirm-delete-role-{{ $role->id }}">
                        <flux:button 
                            variant="danger"
                            wire:click="$dispatch('open-modal', 'confirm-delete-role-{{ $role->id }}')"
                        >
                            {{ __('Delete Role') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>

            <!-- Role Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Basic Information') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Role Name') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Description') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->description ?? 'No description provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Created At') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Last Updated') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->updated_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Statistics -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Statistics') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Users') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->users()->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Total Permissions') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $role->permissions->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Permissions -->
            <div class="mt-6">
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Assigned Permissions') }}</h3>
                    @if($role->permissions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($role->permissions as $permission)
                                <div class="flex items-center p-2 bg-white dark:bg-neutral-800 rounded border">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $permission->name }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('No permissions assigned to this role.') }}</p>
                    @endif
                </div>
            </div>

            <!-- Users with this Role -->
            <div class="mt-6">
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Users with this Role') }}</h3>
                    @if($this->users->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                                <thead class="bg-white dark:bg-neutral-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Name') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Email') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Status') }}
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                            {{ __('Actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-neutral-800 divide-y divide-neutral-200 dark:divide-neutral-700">
                                    @foreach($this->users as $user)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $user->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ $user->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                                    {{ ucfirst($user->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <flux:button 
                                                    :href="route('admin.users.show', $user)" 
                                                    size="sm" 
                                                    variant="outline"
                                                    wire:navigate
                                                >
                                                    {{ __('View') }}
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        @if($this->users->hasPages())
                            <div class="mt-4 border-t border-neutral-200 dark:border-neutral-700 px-6 py-4">
                                {{ $this->users->links() }}
                            </div>
                        @endif
                    @else
                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('No users assigned to this role.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Role Modal -->
    <flux:modal name="confirm-delete-role-{{ $role->id }}" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to delete this role? This action cannot be undone. All associated data will be permanently deleted.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button 
                    variant="danger" 
                    wire:click="deleteRole"
                >
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
