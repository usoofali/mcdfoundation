@props([
    'activities' => collect(),
    'title' => 'Recent Activities',
    'showUser' => true,
    'maxItems' => 10
])

<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">{{ $title }}</h3>
    </div>
    <div class="p-6">
        @if($activities->isNotEmpty())
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($activities->take($maxItems) as $index => $activity)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                                            <flux:icon name="user" class="w-4 h-4 text-white" />
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                        <div>
                                            <p class="text-sm text-gray-500">
                                                @if($showUser && $activity->user)
                                                    <span class="font-medium text-gray-900">{{ $activity->user->name }}</span>
                                                @endif
                                                {{ $activity->action }}
                                                <span class="font-medium text-gray-900">{{ class_basename($activity->entity_type) }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                            <time datetime="{{ $activity->created_at->toISOString() }}">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <div class="text-center py-6">
                <flux:icon name="clock" class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No recent activities</h3>
                <p class="mt-1 text-sm text-gray-500">Activities will appear here as they happen.</p>
            </div>
        @endif
    </div>
</div>
