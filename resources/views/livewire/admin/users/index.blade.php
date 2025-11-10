<?php

use App\Models\User;
use App\Models\Role;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'User Management'])] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';
    public array $roles = [];

    public function mount(): void
    {
        $this->roles = Role::orderBy('name')->get()->toArray();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(User $user): void
    {
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You cannot delete your own account.',
            ]);
            return;
        }

        $user->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    public function toggleUserStatus(User $user): void
    {
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You cannot change your own status.',
            ]);
            return;
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'User status updated successfully.',
        ]);
    }

    public function with(): array
    {
        $query = User::query()
            ->with('role')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role_id', $this->roleFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            });

        return [
            'users' => $query->paginate(15),
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="leading-tight text-xl font-semibold text-gray-900 dark:text-white">{{ __('User Management') }}</h2>
        <flux:button :href="route('admin.users.create')" primary wire:navigate class="gap-2">
            <flux:icon name="user-plus" class="size-4" />
            {{ __('Add New User') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Search and Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <flux:input 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search users...') }}" 
                    icon="magnifying-glass"
                />
                
                <flux:select wire:model.live="roleFilter" placeholder="{{ __('Filter by Role') }}">
                    <option value="">{{ __('All Roles') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                    @endforeach
                </flux:select>
                
                <flux:select wire:model.live="statusFilter" placeholder="{{ __('Filter by Status') }}">
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </flux:select>
            </div>

            <!-- Users Table -->
            @if($users->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('User') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Role') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Last Login') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($users as $user)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 flex-shrink-0">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-600">
                                                    <span class="text-sm font-medium text-neutral-600 dark:text-neutral-300">
                                                        {{ $user->initials() }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                                    {{ $user->name }}
                                                </div>
                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $user->role?->name ?? 'No Role' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                            {{ ucfirst($user->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-neutral-500 dark:text-neutral-400">
                                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button 
                                                :href="route('admin.users.show', $user)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('View') }}
                                            </flux:button>
                                            
                                            <flux:button 
                                                :href="route('admin.users.edit', $user)" 
                                                size="sm" 
                                                variant="outline"
                                                wire:navigate
                                            >
                                                {{ __('Edit') }}
                                            </flux:button>
                                            
                                            @if($user->id !== auth()->id())
                                                <flux:button 
                                                    wire:click="toggleUserStatus({{ $user->id }})"
                                                    size="sm" 
                                                    variant="{{ $user->status === 'active' ? 'danger' : 'primary' }}"
                                                    wire:confirm="Are you sure you want to {{ $user->status === 'active' ? 'deactivate' : 'activate' }} this user?"
                                                >
                                                    {{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}
                                                </flux:button>
                                                
                                                <flux:button 
                                                    wire:click="deleteUser({{ $user->id }})"
                                                    size="sm" 
                                                    variant="danger"
                                                    wire:confirm="Are you sure you want to delete this user? This action cannot be undone."
                                                >
                                                    {{ __('Delete') }}
                                                </flux:button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            @else
                <x-empty-state 
                    title="{{ __('No Users Found') }}" 
                    description="{{ __('No users match your current search criteria.') }}"
                />
            @endif
        </div>
    </div>
</div>
