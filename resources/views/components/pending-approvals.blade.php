@props([
    'approvals' => collect(),
    'title' => 'Pending Approvals',
    'type' => 'loans'
])

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
    </div>
    <div class="p-6">
        @if($approvals->isNotEmpty())
            <div class="space-y-4">
                @foreach($approvals as $approval)
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                @if($type === 'loans')
                                    {{ $approval->member->full_name ?? 'Unknown Member' }}
                                @elseif($type === 'claims')
                                    {{ $approval->member->full_name ?? 'Unknown Member' }}
                                @else
                                    {{ $approval->member->full_name ?? 'Unknown Member' }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ ucfirst($approval->status) }}
                                @if(isset($approval->amount))
                                    - ₦{{ number_format($approval->amount, 2) }}
                                @endif
                                @if(isset($approval->billed_amount))
                                    - ₦{{ number_format($approval->billed_amount, 2) }}
                                @endif
                            </p>
                        </div>
                        <flux:button size="sm" variant="primary" 
                            :href="route($type . '.show', $approval->id)" 
                            wire:navigate>
                            Review
                        </flux:button>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6">
                <flux:icon name="check-circle" class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No pending approvals</h3>
                <p class="mt-1 text-sm text-gray-500">All items are up to date.</p>
            </div>
        @endif
    </div>
</div>
