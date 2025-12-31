<?php

namespace App\Services\Dashboard\Widgets;

use App\Models\User;

abstract class BaseWidget
{
    /**
     * Determine if the widget can be viewed by the user.
     */
    abstract public function canView(User $user): bool;

    /**
     * Get the widget data for the given user.
     */
    abstract public function getData(User $user): array;

    /**
     * Get the widget configuration.
     */
    abstract public function getConfig(): array;

    /**
     * Get the widget type.
     */
    public function getType(): string
    {
        return 'base';
    }

    /**
     * Get the widget order/priority.
     */
    public function getOrder(): int
    {
        return $this->getConfig()['order'] ?? 999;
    }

    /**
     * Get the widget category.
     */
    public function getCategory(): ?string
    {
        return $this->getConfig()['category'] ?? null;
    }
}
