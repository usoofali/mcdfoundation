<?php

use App\Models\HealthcareProvider;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Add Healthcare Provider'])] class extends Component {
    public $name = '';
    public $address = '';
    public $contact = '';
    public $services = '';
    public $status = 'active';

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

        HealthcareProvider::create($validated);

        session()->flash('success', 'Healthcare provider created successfully.');
        $this->redirect(route('admin.healthcare-providers.index'), navigate: true);
    }
}; ?>

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <flux:heading size="xl" class="font-bold">Add Healthcare Provider</flux:heading>
            <flux:button variant="ghost" href="{{ route('admin.healthcare-providers.index') }}" wire:navigate>
                Back to List
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
                    <flux:button type="submit" variant="primary">Create Provider</flux:button>
                    <flux:button type="button" variant="ghost" href="{{ route('admin.healthcare-providers.index') }}"
                        wire:navigate>
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>