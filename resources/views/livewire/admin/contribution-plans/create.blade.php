<?php

use App\Models\ContributionPlan;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Create Contribution Plan'])] class extends Component
{
    public string $display_name = '';
    public string $description = '';
    public string $amount = '';
    public string $frequency = 'monthly';
    public bool $is_active = true;

    public function save(): void
    {
        $this->validate([
            'display_name' => ['required', 'string', 'max:255', 'unique:contribution_plans,display_name'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:daily,weekly,monthly,quarterly,annual', 'unique:contribution_plans,frequency'],
            'is_active' => ['boolean'],
        ]);

        ContributionPlan::create([
            'name' => $this->frequency,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Contribution plan created successfully.',
        ]);

        $this->redirect(route('admin.contribution-plans.index'), navigate: true);
    }

    public function cancel(): void
    {
        $this->redirect(route('admin.contribution-plans.index'), navigate: true);
    }
}; ?>

<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">{{ __('Create New Contribution Plan') }}</h2>
        <flux:button wire:click="cancel" variant="outline">
            {{ __('Cancel') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="save" class="space-y-6">
                <!-- Plan Information -->
                <div class="grid grid-cols-1 gap-6">
                    <flux:input 
                        wire:model="display_name" 
                        label="{{ __('Plan Name') }}" 
                        placeholder="{{ __('Enter plan name') }}"
                        required
                    />
                    
                    <flux:textarea 
                        wire:model="description" 
                        label="{{ __('Description') }}" 
                        placeholder="{{ __('Enter plan description') }}"
                        rows="3"
                    />
                </div>

                <!-- Amount and Frequency -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <flux:input 
                        wire:model="amount" 
                        type="number" 
                        step="0.01"
                        label="{{ __('Amount (₦)') }}" 
                        placeholder="{{ __('Enter amount') }}"
                        required
                    />
                    
                    <flux:select wire:model="frequency" label="{{ __('Frequency') }}" required>
                        <option value="daily">{{ __('Daily') }}</option>
                        <option value="weekly">{{ __('Weekly') }}</option>
                        <option value="monthly">{{ __('Monthly') }}</option>
                        <option value="quarterly">{{ __('Quarterly') }}</option>
                        <option value="annual">{{ __('Annual') }}</option>
                    </flux:select>
                </div>

                <!-- Status -->
                <div>
                    <flux:checkbox wire:model="is_active" label="{{ __('Active') }}" />
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                        {{ __('Active plans can be selected by members for contributions.') }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <flux:button wire:click="cancel" variant="outline">
                        {{ __('Cancel') }}
                    </flux:button>
                    
                    <flux:button type="submit" primary>
                        {{ __('Create Plan') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
