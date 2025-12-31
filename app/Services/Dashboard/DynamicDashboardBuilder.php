<?php

namespace App\Services\Dashboard;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DynamicDashboardBuilder
{
    public function __construct(
        protected DashboardWidgetRegistry $registry
    ) {
    }

    /**
     * Build a complete dashboard for the given user.
     */
    public function build(User $user): array
    {
        return [
            'role' => $user->role?->name ?? 'staff',
            'title' => $this->getDashboardTitle($user),
            'location_info' => $this->getLocationInfo($user),
            'stats' => $this->buildStats($user),
            'charts' => $this->buildCharts($user),
            'quick_actions' => $this->buildQuickActions($user),
            'recent_activities' => $this->getRecentActivities($user),
            'pending_approvals' => new Collection, // Handled by individual widgets
        ];
    }

    /**
     * Build stats section from available widgets.
     */
    protected function buildStats(User $user): array
    {
        $widgets = $this->registry->getAvailableWidgets($user, 'stats');
        $stats = [];

        foreach ($widgets as $widgetData) {
            $widget = $widgetData['widget'];
            $widgetStats = $widget->getData($user);

            if (is_array($widgetStats)) {
                $stats = array_merge($stats, $widgetStats);
            }
        }

        // Sort by order
        usort($stats, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        return $stats;
    }

    /**
     * Build charts section from available widgets.
     */
    protected function buildCharts(User $user): array
    {
        $widgets = $this->registry->getAvailableWidgets($user, 'charts');
        $charts = [];

        foreach ($widgets as $widgetData) {
            $widget = $widgetData['widget'];
            $key = $widgetData['key'];

            $charts[$key] = $widget->getData($user);
        }

        return $charts;
    }

    /**
     * Build quick actions section from available widgets.
     */
    protected function buildQuickActions(User $user): array
    {
        $widgets = $this->registry->getAvailableWidgets($user, 'actions');
        $actions = [];

        foreach ($widgets as $widgetData) {
            $widget = $widgetData['widget'];
            $widgetActions = $widget->getData($user);

            if (is_array($widgetActions)) {
                $actions = array_merge($actions, $widgetActions);
            }
        }

        return $actions;
    }

    /**
     * Get dashboard title based on user role.
     */
    protected function getDashboardTitle(User $user): string
    {
        $roleName = $user->role?->name ?? 'Staff';

        return match ($roleName) {
            'Super Admin', 'System Admin' => 'System Overview',
            'Finance Officer' => 'Finance Officer Dashboard',
            'Health Officer' => 'Health Officer Dashboard',
            'Program Officer' => 'Program Officer Dashboard',
            default => 'Dashboard',
        };
    }

    /**
     * Get user location information.
     */
    protected function getLocationInfo(User $user): ?array
    {
        // Super Admin and System Admin don't have location restrictions
        if ($user->hasRole('Super Admin') || $user->hasRole('System Admin')) {
            return null;
        }

        $locationInfo = [];

        if ($user->state_id) {
            $locationInfo['state'] = $user->state?->name ?? 'Unknown State';
        }

        if ($user->lga_id) {
            $locationInfo['lga'] = $user->lga?->name ?? 'Unknown LGA';
        }

        return !empty($locationInfo) ? $locationInfo : null;
    }

    /**
     * Get recent activities.
     */
    protected function getRecentActivities(User $user, int $limit = 15): Collection
    {
        $query = AuditLog::with(['user'])->latest();

        // Apply location filtering for non-admin users
        if (!($user->hasRole('Super Admin') || $user->hasRole('System Admin'))) {
            if ($user->state_id || $user->lga_id) {
                $query->whereHas('user', function ($q) use ($user) {
                    if ($user->state_id) {
                        $q->where('state_id', $user->state_id);
                    }
                    if ($user->lga_id) {
                        $q->where('lga_id', $user->lga_id);
                    }
                });
            }
        }

        return $query->limit($limit)->get();
    }
}
