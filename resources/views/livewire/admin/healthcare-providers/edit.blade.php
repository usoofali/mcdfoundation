<?php

use App\Models\HealthcareProvider;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Edit Healthcare Provider'])] class extends Component {
    public HealthcareProvider $provider;
    public $name;
    public $address;
    public $contact;
    public $services;
    public $status;

    public function mount(HealthcareProvider $provider): void
    {
        $this->provider = $provider;
        $this->name = $provider->name;
        $this->address = $provider->address;
        $this->contact = $provider->contact;
        $this->services = is_array($provider->services) ? implode(', ', $provider->services) : '';
        $this->status = $provider->status;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'contact' => 'required|string|max:255',
            'services' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Convert services string to array
        $validated['services'] = $validated['services']
            ? array_map('trim', explode(',', $validated['services']))
            : [];

        $this->provider->update($validated);

        session()->flash('success', 'Healthcare provider updated successfully.');
        $this->redirect(route('admin.healthcare-providers.show', $this->provider), navigate: true);
    }
}; ?>

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="font-bold">Edit Healthcare Provider</flux:heading>
            <flux:button variant="ghost" href="{{ route('admin.healthcare-providers.show', $provider) }}" wire:navigate>
                Cancel
            </flux:button>
        </div>

        <!-- Form -->
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="name" label="Provider Name" placeholder="Enter provider name" required />

                <flux:textarea wire:model="address" label="Address" placeholder="Enter full address" rows="3"
                    required />

                <flux:input wire:model="contact" label="Contact" placeholder="Phone number or email" required />

                <flux:textarea wire:model="services" label="Services (comma-separated)"
                    placeholder="e.g., General Consultation, Surgery, Dental" rows="3" />

                <flux:select wire:model="status" label="Status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </flux:select>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Update Provider</flux:button>
                    <flux:button type="button" variant="ghost"
                        href="{{ route('admin.healthcare-providers.show', $provider) }}" wire:navigate>
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>