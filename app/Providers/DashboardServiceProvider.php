<?php

namespace App\Providers;

use App\Services\Dashboard\DashboardWidgetRegistry;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the widget registry as a singleton
        $this->app->singleton(DashboardWidgetRegistry::class, function ($app) {
            return new DashboardWidgetRegistry();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Get the widget registry
        $registry = $this->app->make(DashboardWidgetRegistry::class);

        // Register all widgets from config
        $widgets = config('dashboard.widgets', []);

        // Register stats widgets
        if (isset($widgets['stats'])) {
            $registry->registerMany($widgets['stats'], 'stats');
        }

        // Register chart widgets
        if (isset($widgets['charts'])) {
            $registry->registerMany($widgets['charts'], 'charts');
        }

        // Register action widgets
        if (isset($widgets['actions'])) {
            $registry->registerMany($widgets['actions'], 'actions');
        }
    }
}
