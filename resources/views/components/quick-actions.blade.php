@props([
    'actions' => [],
    'title' => 'Quick Actions',
    'columns' => 1
])

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
    </div>
    <div class="p-6">
        @if(!empty($actions))
            <div class="grid grid-cols-{{ $columns }} gap-3">
                @foreach($actions as $action)
                    <flux:button 
                        variant="secondary" 
                        :href="$action['url']" 
                        wire:navigate
                        class="justify-start"
                    >
                        <flux:icon name="{{ $action['icon'] }}" class="w-4 h-4 mr-2" />
                        {{ $action['title'] }}
                    </flux:button>
                @endforeach
            </div>
        @else
            <div class="text-center py-6">
                <flux:icon name="lightning-bolt" class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No quick actions</h3>
                <p class="mt-1 text-sm text-gray-500">Quick actions will appear here based on your role.</p>
            </div>
        @endif
    </div>
</div>
