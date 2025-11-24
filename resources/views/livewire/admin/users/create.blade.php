<?php

use App\Models\Lga;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create User'])] class extends Component
{
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

    public function mount(): void
    {
        $this->roles = Role::orderBy('name')->get()->toArray();
        $this->states = State::orderBy('name')->get()->toArray();
    }

    public function updatedStateId(string $stateId): void
    {
        $this->lgas = Lga::where('state_id', $stateId)->orderBy('name')->get()->toArray();
        $this->lga_id = ''; // Reset LGA when state changes
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'state_id' => ['nullable', 'exists:states,id'],
            'lga_id' => ['nullable', 'exists:lgas,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'status' => ['required', 'in:active,inactive'],
            'account_number' => ['required', 'string', 'max:50'],
            'account_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
        ]);

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
            'address' => $this->address,
            'state_id' => $this->state_id ?: null,
            'lga_id' => $this->lga_id ?: null,
            'role_id' => $this->role_id,
            'status' => $this->status,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'bank_name' => $this->bank_name,
            'email_verified_at' => now(),
        ]);

        session()->flash('success', 'User created successfully.');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.users.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Create New User') }}</h2>
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

                <!-- Password -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="password" 
                        type="password" 
                        label="{{ __('Password') }}" 
                        placeholder="{{ __('Enter password') }}"
                        required
                    />
                    
                    <flux:input 
                        wire:model="password_confirmation" 
                        type="password" 
                        label="{{ __('Confirm Password') }}" 
                        placeholder="{{ __('Confirm password') }}"
                        required
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
                        {{ __('Create User') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
