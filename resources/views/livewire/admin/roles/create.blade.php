<?php

use App\Models\Role;
use App\Models\Permission;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create Role'])] class extends Component
{
    public string $name = '';
    public string $description = '';
    public array $permissions = [];
    public array $availablePermissions = [];

    public function mount(): void
    {
        $this->availablePermissions = Permission::orderBy('name')->get()->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $role->permissions()->sync($this->permissions);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Role created successfully.',
        ]);

        $this->redirect(route('admin.roles.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.roles.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Create New Role') }}</h2>
        <flux:button wire:click="cancel" variant="outline">
            {{ __('Cancel') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="space-y-6">
                <!-- Role Information -->
                <div class="grid grid-cols-1 gap-6">
                    <flux:input 
                        wire:model="name" 
                        label="{{ __('Role Name') }}" 
                        placeholder="{{ __('Enter role name') }}"
                        required
                    />
                    
                    <flux:textarea 
                        wire:model="description" 
                        label="{{ __('Description') }}" 
                        placeholder="{{ __('Enter role description') }}"
                        rows="3"
                    />
                </div>

                <!-- Permissions -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Permissions') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($availablePermissions as $permission)
                            <div class="flex items-center">
                                <flux:checkbox 
                                    wire:model="permissions" 
                                    value="{{ $permission['id'] }}"
                                    id="permission_{{ $permission['id'] }}"
                                />
                                <label for="permission_{{ $permission['id'] }}" class="ml-2 text-sm text-neutral-700 dark:text-neutral-300">
                                    {{ $permission['name'] }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <flux:button wire:click="cancel" variant="outline">
                        {{ __('Cancel') }}
                    </flux:button>
                    
                    <flux:button type="submit" primary>
                        {{ __('Create Role') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
