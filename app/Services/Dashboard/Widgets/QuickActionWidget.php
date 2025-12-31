<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;

abstract class QuickActionWidget extends BaseWidget
{
    /**
     * Get the widget type.
     */
    public function getType(): string
    {
        return 'action';
    }

    /**
     * Get quick actions formatted for display.
     * 
     * @return array Array of action items, each with:
     *               - title: string
     *               - url: string (route URL)
     *               - icon: string (Heroicon name)
     *               - color: string (blue, green, red, yellow, purple, gray)
     *               - permission: string|null (optional permission check)
     */
    abstract public function getData(User $user): array;

    /**
     * Filter actions based on user permissions.
     */
    protected function filterByPermissions(array $actions, User $user): array
    {
        return array_filter($actions, function ($action) use ($user) {
            if (!isset($action['permission'])) {
                return true;
            }

            return $user->hasPermission($action['permission']);
        });
    }

    /**
     * Create an action item.
     */
    protected function createAction(
        string $title,
        string $url,
        string $icon,
        string $color = 'blue',
        ?string $permission = null
    ): array {
        return [
            'title' => $title,
            'url' => $url,
            'icon' => $icon,
            'color' => $color,
            'permission' => $permission,
        ];
    }
}
