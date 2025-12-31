<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Services\Dashboard\Widgets\BaseWidget;
use Illuminate\Support\Collection;

class DashboardWidgetRegistry
{
    /**
     * Registered widgets by type.
     */
    protected array $widgets = [
        'stats' => [],
        'charts' => [],
        'actions' => [],
    ];

    /**
     * Register a widget.
     */
    public function register(string $key, string $widgetClass, string $type = 'stats'): void
    {
        if (!isset($this->widgets[$type])) {
            $this->widgets[$type] = [];
        }

        $this->widgets[$type][$key] = $widgetClass;
    }

    /**
     * Register multiple widgets at once.
     */
    public function registerMany(array $widgets, string $type = 'stats'): void
    {
        foreach ($widgets as $key => $widgetClass) {
            $this->register($key, $widgetClass, $type);
        }
    }

    /**
     * Get all registered widgets of a specific type.
     */
    public function getWidgetsByType(string $type): array
    {
        return $this->widgets[$type] ?? [];
    }

    /**
     * Get all available widgets for a user (filtered by permissions).
     */
    public function getAvailableWidgets(User $user, ?string $type = null): Collection
    {
        $widgets = $type ? [$type => $this->getWidgetsByType($type)] : $this->widgets;
        $available = collect();

        foreach ($widgets as $widgetType => $widgetClasses) {
            foreach ($widgetClasses as $key => $widgetClass) {
                $widget = app($widgetClass);

                if ($widget instanceof BaseWidget && $widget->canView($user)) {
                    $available->push([
                        'key' => $key,
                        'type' => $widgetType,
                        'widget' => $widget,
                        'order' => $widget->getOrder(),
                    ]);
                }
            }
        }

        return $available->sortBy('order');
    }

    /**
     * Get a specific widget instance.
     */
    public function getWidget(string $key, string $type = 'stats'): ?BaseWidget
    {
        $widgetClass = $this->widgets[$type][$key] ?? null;

        if (!$widgetClass) {
            return null;
        }

        return app($widgetClass);
    }

    /**
     * Check if a widget is registered.
     */
    public function has(string $key, string $type = 'stats'): bool
    {
        return isset($this->widgets[$type][$key]);
    }

    /**
     * Get all registered widget keys by type.
     */
    public function getRegisteredKeys(string $type = 'stats'): array
    {
        return array_keys($this->widgets[$type] ?? []);
    }
}
