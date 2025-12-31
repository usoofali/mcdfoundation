<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;

abstract class ChartWidget extends BaseWidget
{
    /**
     * Get the widget type.
     */
    public function getType(): string
    {
        return 'chart';
    }

    /**
     * Get chart data formatted for Chart.js.
     * 
     * @return array Chart configuration with:
     *               - labels: array
     *               - data: array
     *               - type: string (line, bar, doughnut, pie)
     *               - options: array (optional Chart.js options)
     */
    abstract public function getData(User $user): array;

    /**
     * Get default chart colors.
     */
    protected function getDefaultColors(): array
    {
        return [
            'rgb(59, 130, 246)',   // blue
            'rgb(34, 197, 94)',    // green
            'rgb(234, 179, 8)',    // yellow
            'rgb(239, 68, 68)',    // red
            'rgb(168, 85, 247)',   // purple
            'rgb(236, 72, 153)',   // pink
            'rgb(20, 184, 166)',   // teal
            'rgb(251, 146, 60)',   // orange
        ];
    }

    /**
     * Format chart data for line/bar charts.
     */
    protected function formatTimeSeriesData(array $data, string $type = 'line'): array
    {
        return [
            'type' => $type,
            'labels' => array_keys($data),
            'data' => array_values($data),
        ];
    }

    /**
     * Format chart data for doughnut/pie charts.
     */
    protected function formatDistributionData(array $data): array
    {
        return [
            'type' => 'doughnut',
            'labels' => array_keys($data),
            'data' => array_values($data),
            'backgroundColor' => array_slice($this->getDefaultColors(), 0, count($data)),
        ];
    }
}
