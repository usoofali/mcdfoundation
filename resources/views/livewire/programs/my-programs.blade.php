<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'My Programs'])] class extends Component {
    public function with(): array
    {
        $member = auth()->user()->member;

        return [
            'enrollments' => ProgramEnrollment::where('member_id', $member->id)
                ->with(['program'])
                ->orderBy('created_at', 'desc')
                ->get(),
            'stats' => [
                'total' => ProgramEnrollment::where('member_id', $member->id)->count(),
                'active' => ProgramEnrollment::where('member_id', $member->id)->where('status', 'active')->count(),
                'completed' => ProgramEnrollment::where('member_id', $member->id)->where('status', 'completed')->count(),
            ],
        ];
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('My Programs') }}</h2>
        <flux:button :href="route('my.programs.browse')" variant="primary" wire:navigate>
            {{ __('Browse Programs') }}
        </flux:button>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Enrollments') }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Active') }}</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['active'] }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Completed') }}</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['completed'] }}</p>
            </div>
        </div>

        <!-- Enrollments List -->
        @if($enrollments->count() > 0)
            <div class="space-y-4">
                @foreach($enrollments as $enrollment)
                    <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $enrollment->program->title }}
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                    ($enrollment->status === 'completed' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200') }}">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-400">
                                    {{ $enrollment->program->description }}
                                </p>

                                <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Enrolled On') }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $enrollment->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    @if($enrollment->program->start_date)
                                        <div>
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Start Date') }}</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $enrollment->program->start_date->format('M d, Y') }}
                                            </p>
                                        </div>
                                    @endif
                                    @if($enrollment->program->end_date)
                                        <div>
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('End Date') }}</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $enrollment->program->end_date->format('M d, Y') }}
                                            </p>
                                        </div>
                                    @endif
                                    @if($enrollment->program->capacity)
                                        <div>
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Capacity') }}</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $enrollment->program->enrollments_count ?? 0 }} /
                                                {{ $enrollment->program->capacity }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="ml-4">
                                <flux:button :href="route('programs.show', $enrollment->program)" size="sm" variant="outline"
                                    wire:navigate>
                                    {{ __('View Details') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div
                class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('No program enrollments') }}</h3>
                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ __('Browse available programs and enroll to participate.') }}
                </p>
                <div class="mt-6">
                    <flux:button :href="route('my.programs.browse')" variant="primary" wire:navigate>
                        {{ __('Browse Programs') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>