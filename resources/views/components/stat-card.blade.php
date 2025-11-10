@props([
    'title',
    'value',
    'icon' => 'chart-bar',
    'color' => 'blue',
    'trend' => null,
    'href' => null
])

@php
    $colorClasses = [
        'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200',
        'green' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-200',
        'yellow' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-200',
        'red' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-200',
        'purple' => 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-200',
        'gray' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    ];
    
    $trendColors = [
        'positive' => 'text-green-600',
        'negative' => 'text-red-600',
        'neutral' => 'text-gray-600',
    ];
    
    $trendColor = 'neutral';
    if ($trend && str_starts_with($trend, '+')) {
        $trendColor = 'positive';
    } elseif ($trend && str_starts_with($trend, '-')) {
        $trendColor = 'negative';
    }
@endphp

<div class="bg-white dark:bg-zinc-800 shadow rounded-lg p-6 {{ $href ? 'hover:shadow-md transition-shadow cursor-pointer' : '' }}" 
     @if($href) onclick="window.location.href='{{ $href }}'" @endif>
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <div class="w-8 h-8 {{ $colorClasses[$color] }} rounded-md flex items-center justify-center">
                <flux:icon name="{{ $icon }}" class="w-5 h-5" />
            </div>
        </div>
        <div class="ml-4 flex-1">
            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">{{ $title }}</p>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $value }}</p>
            @if($trend)
                <p class="text-xs {{ $trendColors[$trendColor] }} mt-1">{{ $trend }}</p>
            @endif
        </div>
    </div>
</div>