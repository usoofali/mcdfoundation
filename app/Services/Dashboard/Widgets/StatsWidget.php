<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;

abstract class StatsWidget extends BaseWidget
{
    /**
     * Get the widget type.
     */
    public function getType(): string
    {
        return 'stats';
    }

    /**
     * Get stats data formatted for display.
     * 
     * @return array Array of stat items, each with:
     *               - title: string
     *               - value: string|int
     *               - icon: string (Heroicon name)
     *               - color: string (blue, green, red, yellow, purple, gray)
     *               - trend: string|null (optional, e.g., "+12.5%")
     *               - order: int (for sorting)
     */
    abstract public function getData(User $user): array;

    /**
     * Format a number with appropriate suffix (K, M, B).
     */
    protected function formatNumber(int|float $number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 1) . 'B';
        }
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }

        return number_format($number);
    }

    /**
     * Format currency amount.
     */
    protected function formatCurrency(int|float $amount): string
    {
        return '₦' . number_format($amount, 2);
    }

    /**
     * Calculate percentage change.
     */
    protected function calculateTrend(int|float $current, int|float $previous): ?string
    {
        if ($previous == 0) {
            return $current > 0 ? 'New' : null;
        }

        $change = (($current - $previous) / $previous) * 100;
        $formatted = number_format($change, 1);

        if ($change > 0) {
            return "+{$formatted}%";
        } elseif ($change < 0) {
            return "{$formatted}%";
        }

        return '0%';
    }
}
