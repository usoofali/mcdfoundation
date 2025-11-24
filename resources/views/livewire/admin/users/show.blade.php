<?php

use App\Models\User;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'User Details'])] class extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load(['role', 'state', 'lga', 'member']);
    }

    public function deleteUser(): void
    {
        if ($this->user->id === auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You cannot delete your own account.',
            ]);

            return;
        }

        $this->user->delete();

        session()->flash('success', 'User deleted successfully.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function toggleStatus(): void
    {
        if ($this->user->id === auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You cannot change your own status.',
            ]);

            return;
        }

        $this->user->update(['status' => $this->user->status === 'active' ? 'inactive' : 'active']);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'User status updated successfully.',
        ]);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('User Details') }}</h2>
        <div class="flex flex-wrap items-center gap-2">
            <flux:button :href="route('admin.users.edit', $user)" variant="outline" wire:navigate>
                {{ __('Edit User') }}
            </flux:button>
            <flux:button :href="route('admin.users.index')" variant="outline" wire:navigate>
                {{ __('Back to Users') }}
            </flux:button>
        </div>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- User Profile Header -->
            <div class="flex items-center space-x-6 mb-8">
                <div class="flex-shrink-0">
                    <div class="h-20 w-20 rounded-full bg-neutral-200 dark:bg-neutral-600 flex items-center justify-center">
                        <span class="text-2xl font-medium text-neutral-600 dark:text-neutral-300">
                            {{ $user->initials() }}
                        </span>
                    </div>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                    <p class="text-neutral-600 dark:text-neutral-400">{{ $user->email }}</p>
                    <div class="flex items-center space-x-4 mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $user->role?->name ?? 'No Role' }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($user->id !== auth()->id())
                        <flux:modal.trigger name="confirm-toggle-status-user-{{ $user->id }}">
                            <flux:button 
                                variant="{{ $user->status === 'active' ? 'danger' : 'primary' }}"
                                wire:click="$dispatch('open-modal', 'confirm-toggle-status-user-{{ $user->id }}')"
                            >
                                {{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}
                            </flux:button>
                        </flux:modal.trigger>
                        
                        <flux:modal.trigger name="confirm-delete-user-{{ $user->id }}">
                            <flux:button 
                                variant="danger"
                                wire:click="$dispatch('open-modal', 'confirm-delete-user-{{ $user->id }}')"
                            >
                                {{ __('Delete User') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>
            </div>

            <!-- User Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Personal Information') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Full Name') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Email Address') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone Number') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->phone ?? 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->address ?? 'Not provided' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Account Details -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Account Details') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Account Number') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->account_number ?? 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Account Name') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->account_name ?? 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Bank Name') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->bank_name ?? 'Not provided' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Location Information -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Location Information') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('State') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->state?->name ?? 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Local Government Area') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->lga?->name ?? 'Not specified' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Account Information -->
                <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Account Information') }}</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Role') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->role?->name ?? 'No Role' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($user->status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Email Verified') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Last Login') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Created At') }}</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y \a\t g:i A') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Member Information -->
                @if($user->member)
                    <div class="bg-neutral-50 dark:bg-neutral-900 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Member Information') }}</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Registration Number') }}</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $user->member->registration_no }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Member Status') }}</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($user->member->status) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Registration Date') }}</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $user->member->registration_date ? $user->member->registration_date->format('M d, Y') : 'Not set' }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4">
                            <flux:button :href="route('members.show', $user->member)" variant="outline" size="sm" wire:navigate>
                                {{ __('View Member Details') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($user->id !== auth()->id())
        <!-- Status Toggle Modal -->
        <flux:modal name="confirm-toggle-status-user-{{ $user->id }}" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Confirm Status Change') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to :action this user?', ['action' => $user->status === 'active' ? __('deactivate') : __('activate')]) }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button 
                        variant="{{ $user->status === 'active' ? 'danger' : 'primary' }}"
                        wire:click="toggleStatus"
                    >
                        {{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Delete Modal -->
        <flux:modal name="confirm-delete-user-{{ $user->id }}" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                    <flux:subheading>
                        {{ __('Are you sure you want to delete this user? This action cannot be undone. All associated data will be permanently deleted.') }}
                    </flux:subheading>
                </div>

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button 
                        variant="danger" 
                        wire:click="deleteUser"
                    >
                        {{ __('Delete') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
