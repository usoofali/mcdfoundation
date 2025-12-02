<?php

use App\Models\HealthcareProvider;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app', ['title' => 'Healthcare Providers'])] class extends Component {
    use WithPagination;

    public $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteProvider($providerId): void
    {
        $provider = HealthcareProvider::findOrFail($providerId);

        // Check if provider has any associated members or claims
        if ($provider->healthClaims()->count() > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete provider with existing health claims.',
            ]);
            $this->dispatch('close-modal', 'confirm-delete-provider-' . $providerId);
            return;
        }

        $provider->delete();

        // Close the modal
        $this->dispatch('close-modal', 'confirm-delete-provider-' . $providerId);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Healthcare provider deleted successfully.',
        ]);
        // Reset pagination if needed
        $this->resetPage();
    }

    public function with(): array
    {
        $query = HealthcareProvider::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%')
                    ->orWhere('contact', 'like', '%' . $this->search . '%');
            });

        return [
            'providers' => $query->latest()->paginate(15),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="space-y-6">
        <!-- Page Header -->
        <div
            class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <flux:heading size="xl" class="font-bold text-neutral-900 dark:text-white">
                        Healthcare Providers
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                        Manage healthcare providers and their services
                    </flux:text>
                </div>
                <div>
                    <flux:button variant="primary" icon="plus-circle" :href="route('admin.healthcare-providers.create')"
                        wire:navigate class="gap-2">
                        {{ __('Add Provider') }}
                    </flux:button>
                </div>
            </div>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <!-- Search -->
            <div class="mb-6">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search providers...') }}"
                    icon="magnifying-glass" />
            </div>

            <!-- Providers Table -->
            @if($providers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                        <thead class="bg-neutral-50 dark:bg-neutral-900">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                                    {{ __('Provider') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Contact') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Status') }}
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                            @foreach($providers as $provider)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-neutral-900 dark:text-white">
                                            {{ $provider->name }}
                                        </div>
                                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                            {{ $provider->address }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-zinc-900 dark:text-zinc-100">
                                            {{ $provider->contact }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                                                                            {{ $provider->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                                            {{ ucfirst($provider->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <flux:button :href="route('admin.healthcare-providers.show', $provider)" size="sm"
                                                variant="outline" wire:navigate>
                                                {{ __('View') }}
                                            </flux:button>

                                            <flux:button :href="route('admin.healthcare-providers.edit', $provider)" size="sm"
                                                variant="outline" wire:navigate>
                                                {{ __('Edit') }}
                                            </flux:button>

                                            <flux:modal.trigger name="confirm-delete-provider-{{ $provider->id }}">
                                                <flux:button size="sm" variant="danger"
                                                    wire:click="$dispatch('open-modal', 'confirm-delete-provider-{{ $provider->id }}')">
                                                    {{ __('Delete') }}
                                                </flux:button>
                                            </flux:modal.trigger>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $providers->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <flux:heading size="sm" class="mt-2 font-medium text-neutral-900 dark:text-white">
                        No Providers Found
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                        No healthcare providers match your current search criteria.
                    </flux:text>
                </div>
            @endif
        </div>

        <!-- Delete Confirmation Modals -->
        @foreach($providers as $provider)
            <flux:modal name="confirm-delete-provider-{{ $provider->id }}" focusable class="max-w-lg">
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">{{ __('Confirm Deletion') }}</flux:heading>
                        <flux:subheading>
                            {{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $provider->name]) }}
                        </flux:subheading>
                    </div>

                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="outline">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>

                        <flux:modal.close>
                            <flux:button variant="danger" wire:click="deleteProvider({{ $provider->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        @endforeach
    </div>
</div>