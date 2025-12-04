<?php

use App\Models\Program;
use App\Models\ProgramEnrollment;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app', ['title' => 'Enroll in Program'])] class extends Component {
    public Program $program;

    public function mount(Program $program): void
    {
        $this->program = $program->load('enrollments');
    }

    public function enroll(): void
    {
        $member = auth()->user()->member;

        // Check if already enrolled
        $existingEnrollment = ProgramEnrollment::where('program_id', $this->program->id)
            ->where('member_id', $member->id)
            ->first();

        if ($existingEnrollment) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You are already enrolled in this program.',
            ]);
            return;
        }

        // Check capacity
        if ($this->program->capacity && $this->program->enrollments()->count() >= $this->program->capacity) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'This program has reached its maximum capacity.',
            ]);
            return;
        }

        try {
            ProgramEnrollment::create([
                'program_id' => $this->program->id,
                'member_id' => $member->id,
                'status' => 'active',
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Successfully enrolled in ' . $this->program->title,
            ]);

            $this->redirect(route('my.programs'), navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to enroll: ' . $e->getMessage(),
            ]);
        }
    }

    public function isEnrolledProperty()
    {
        $member = auth()->user()->member;
        return ProgramEnrollment::where('program_id', $this->program->id)
            ->where('member_id', $member->id)
            ->exists();
    }
}; ?>

<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ __('Enroll in Program') }}</h2>
    </div>
</x-slot>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Program Details -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $program->title }}</h3>

            @if($program->description)
                <p class="mt-4 text-neutral-600 dark:text-neutral-400">{{ $program->description }}</p>
            @endif

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($program->start_date)
                    <div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Start Date') }}</p>
                        <p class="text-lg text-gray-900 dark:text-white">{{ $program->start_date->format('M d, Y') }}</p>
                    </div>
                @endif

                @if($program->end_date)
                    <div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('End Date') }}</p>
                        <p class="text-lg text-gray-900 dark:text-white">{{ $program->end_date->format('M d, Y') }}</p>
                    </div>
                @endif

                @if($program->capacity)
                    <div>
                        <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Capacity') }}</p>
                        <p class="text-lg text-gray-900 dark:text-white">
                            {{ $program->enrollments->count() }} / {{ $program->capacity }}
                            @if($program->enrollments->count() >= $program->capacity)
                                <span class="text-sm text-red-600 dark:text-red-400">({{ __('Full') }})</span>
                            @endif
                        </p>
                    </div>
                @endif

                <div>
                    <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</p>
                    <p class="text-lg text-gray-900 dark:text-white">{{ ucfirst($program->status) }}</p>
                </div>
            </div>
        </div>

        <!-- Enrollment Status -->
        @if($this->isEnrolled)
            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-green-800 dark:text-green-200">
                        <p class="font-medium">{{ __('You are already enrolled in this program') }}</p>
                    </div>
                </div>
            </div>
        @elseif($program->capacity && $program->enrollments->count() >= $program->capacity)
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm text-red-800 dark:text-red-200">
                        <p class="font-medium">{{ __('This program has reached its maximum capacity') }}</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Enroll Button -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ __('Confirm Enrollment') }}</h4>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-6">
                    {{ __('By enrolling in this program, you confirm that you will participate in the scheduled activities and comply with the program requirements.') }}
                </p>

                <div class="flex items-center justify-between">
                    <flux:button :href="route('my.programs.browse')" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button wire:click="enroll" variant="primary">
                        {{ __('Enroll Now') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>
</div>