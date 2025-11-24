<?php

use App\Models\Lga;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Edit User'])] class extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $phone = '';

    public string $address = '';

    public string $state_id = '';

    public string $lga_id = '';

    public string $role_id = '';

    public string $status = 'active';

    public string $account_number = '';

    public string $account_name = '';

    public string $bank_name = '';

    public array $roles = [];

    public array $states = [];

    public array $lgas = [];

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
        $this->state_id = $user->state_id ?? '';
        $this->lga_id = $user->lga_id ?? '';
        $this->role_id = $user->role_id ?? '';
        $this->status = $user->status;
        $this->account_number = $user->account_number ?? '';
        $this->account_name = $user->account_name ?? '';
        $this->bank_name = $user->bank_name ?? '';

        $this->roles = Role::orderBy('name')->get()->toArray();
        $this->states = State::orderBy('name')->get()->toArray();

        if ($this->state_id) {
            $this->lgas = Lga::where('state_id', $this->state_id)->orderBy('name')->get()->toArray();
        }
    }

    public function updatedStateId(string $stateId): void
    {
        $this->lgas = Lga::where('state_id', $stateId)->orderBy('name')->get()->toArray();
        $this->lga_id = ''; // Reset LGA when state changes
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'state_id' => ['nullable', 'exists:states,id'],
            'lga_id' => ['nullable', 'exists:lgas,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:active,inactive'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
        ];

        if ($this->password) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $this->validate($rules);

        $updateData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'state_id' => $this->state_id ?: null,
            'lga_id' => $this->lga_id ?: null,
            'role_id' => $this->role_id,
            'status' => $this->status,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'bank_name' => $this->bank_name,
        ];

        if ($this->password) {
            $updateData['password'] = Hash::make($this->password);
        }

        $this->user->update($updateData);

        session()->flash('success', 'User updated successfully.');

        $this->redirect(route('admin.users.show', $this->user), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.users.show', $this->user), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Edit User') }}</h2>
        <flux:button wire:click="cancel" variant="outline">
            {{ __('Cancel') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="space-y-6">
                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="name" 
                        label="{{ __('Full Name') }}" 
                        placeholder="{{ __('Enter full name') }}"
                        required
                    />
                    
                    <flux:input 
                        wire:model="email" 
                        type="email" 
                        label="{{ __('Email Address') }}" 
                        placeholder="{{ __('Enter email address') }}"
                        required
                    />
                </div>

                <!-- Password (Optional) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="password" 
                        type="password" 
                        label="{{ __('New Password') }}" 
                        placeholder="{{ __('Leave blank to keep current password') }}"
                    />
                    
                    <flux:input 
                        wire:model="password_confirmation" 
                        type="password" 
                        label="{{ __('Confirm New Password') }}" 
                        placeholder="{{ __('Confirm new password') }}"
                    />
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="phone" 
                        label="{{ __('Phone Number') }}" 
                        placeholder="{{ __('Enter phone number') }}"
                    />
                    
                    <flux:textarea 
                        wire:model="address" 
                        label="{{ __('Address') }}" 
                        placeholder="{{ __('Enter address') }}"
                        rows="3"
                    />
                </div>

                <!-- Account Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <flux:input 
                        wire:model="account_number" 
                        label="{{ __('Account Number') }}" 
                        placeholder="{{ __('Enter account number') }}"
                        required
                    />
                    
                    <flux:input 
                        wire:model="account_name" 
                        label="{{ __('Account Name') }}" 
                        placeholder="{{ __('Enter account name') }}"
                        required
                    />
                    
                    <flux:input 
                        wire:model="bank_name" 
                        label="{{ __('Bank Name') }}" 
                        placeholder="{{ __('Enter bank name') }}"
                        required
                    />
                </div>

                <!-- Location -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:select wire:model.live="state_id" label="{{ __('State') }}">
                        <option value="">{{ __('Select State') }}</option>
                        @foreach($states as $state)
                            <option value="{{ $state['id'] }}">{{ $state['name'] }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="lga_id" label="{{ __('Local Government Area') }}">
                        <option value="">{{ __('Select LGA') }}</option>
                        @foreach($lgas as $lga)
                            <option value="{{ $lga['id'] }}">{{ $lga['name'] }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Role and Status -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:select wire:model="role_id" label="{{ __('Role') }}" required>
                        <option value="">{{ __('Select Role') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                        @endforeach
                    </flux:select>
                    
                    <flux:select wire:model="status" label="{{ __('Status') }}" required>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </flux:select>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <flux:button wire:click="cancel" variant="outline">
                        {{ __('Cancel') }}
                    </flux:button>
                    
                    <flux:button type="submit" primary>
                        {{ __('Update User') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
