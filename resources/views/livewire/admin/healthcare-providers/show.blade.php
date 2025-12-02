<?php

use App\Models\HealthcareProvider;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Healthcare Provider Details'])] class extends Component {
    public HealthcareProvider $provider;

    public function mount(HealthcareProvider $provider): void
    {
        $this->provider = $provider;
    }
}; ?>

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="font-bold">{{ $provider->name }}</flux:heading>
                <flux:text class="mt-1 text-sm text-neutral-500">Healthcare Provider Details</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:button variant="outline" href="{{ route('admin.healthcare-providers.edit', $provider) }}"
                    wire:navigate>
                    Edit
                </flux:button>
                <flux:button variant="ghost" href="{{ route('admin.healthcare-providers.index') }}" wire:navigate>
                    Back to List
                </flux:button>
            </div>
        </div>

        <!-- Provider Details -->
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <flux:heading size="sm" class="mb-2">Provider Name</flux:heading>
                    <flux:text>{{ $provider->name }}</flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Status</flux:heading>
                    <span
                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $provider->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($provider->status) }}
                    </span>
                </div>

                <div class="md:col-span-2">
                    <flux:heading size="sm" class="mb-2">Address</flux:heading>
                    <flux:text>{{ $provider->address }}</flux:text>
                </div>

                <div>
                    <flux:heading size="sm" class="mb-2">Contact</flux:heading>
                    <flux:text>{{ $provider->contact }}</flux:text>
                </div>

                @if($provider->services && count($provider->services) > 0)
                    <div class="md:col-span-2">
                        <flux:heading size="sm" class="mb-2">Services Offered</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @foreach($provider->services as $service)
                                <span
                                    class="inline-flex px-3 py-1 text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                    {{ $service }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>