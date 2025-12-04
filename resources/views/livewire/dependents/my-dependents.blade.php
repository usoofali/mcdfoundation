<?php

use App\Models\Dependent;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Dependents'])] class extends Component {
    public $member;

    public function mount(): void
    {
        $this->member = auth()->user()->member;
        $this->member->load('dependents');
    }

    public function with(): array
    {
        return [
            'dependents' => $this->member->dependents,
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Dependents') }}</h2>
        <flux:button :href="route('dependents.manage', $member)" variant="primary" wire:navigate>
            {{ __('Manage Dependents') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Stats Card -->
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <h3 class="text-2xl font-bold">{{ $dependents->count() }}
                {{ Str::plural('Dependent', $dependents->count()) }}</h3>
            <p class="mt-2 text-purple-100">{{ __('Registered under your membership') }}</p>
        </div>

        <!-- Dependents List -->
        @if($dependents->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($dependents as $dependent)
                    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center">
                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ strtoupper(substr($dependent->first_name, 0, 1) . substr($dependent->last_name, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $dependent->full_name }}</h3>
                                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ ucfirst($dependent->relationship) }}</p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-500 dark:text-neutral-400">{{ __('Date of Birth') }}</span>
                                <span class="text-gray-900 dark:text-white font-medium">
                                    {{ $dependent->date_of_birth ? $dependent->date_of_birth->format('M d, Y') : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-neutral-500 dark:text-neutral-400">{{ __('Gender') }}</span>
                                <span
                                    class="text-gray-900 dark:text-white font-medium">{{ ucfirst($dependent->gender ?? 'N/A') }}</span>
                            </div>
                            @if($dependent->phone)
                                <div class="flex justify-between text-sm">
                                    <span class="text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</span>
                                    <span class="text-gray-900 dark:text-white font-medium">{{ $dependent->phone }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div
                class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No dependents registered') }}</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Add your family members as dependents to extend health coverage.') }}
                </p>
                <div class="mt-6">
                    <flux:button :href="route('dependents.manage', $member)" variant="primary" wire:navigate>
                        {{ __('Add Dependent') }}
                    </flux:button>
                </div>
            </div>
        @endif

        <!-- Info Card -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
            <div class="flex gap-3">
                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-medium mb-1">{{ __('About Dependents') }}</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-300">
                        <li>{{ __('Dependents can include spouse, children, and other family members') }}</li>
                        <li>{{ __('Health coverage extends to all registered dependents') }}</li>
                        <li>{{ __('Keep dependent information up to date for accurate records') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>