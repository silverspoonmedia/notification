<?php

namespace App\Domain\Notification\Support\ListActions;

use App\Domain\Identity\Models\User;
use App\Support\ListActions\QueryRowAction;
use App\Support\ListActions\QueryRowActionBuilder;

final class NotificationAudienceQueryRowActions implements QueryRowActionBuilder
{
    /**
     * @return list<QueryRowAction>
     */
    public function resolve(mixed $model, ?User $actor = null): array
    {
        if (! is_array($model) || $actor === null || ! $actor->can('manage settings')) {
            return [];
        }

        $type = $model['type'] ?? null;
        if (! is_string($type) || $type === '') {
            return [];
        }

        return [
            QueryRowAction::nav(
                key: 'edit',
                label: __('ui.actions.edit'),
                icon: 'pencil',
                variant: 'primary',
                route: 'notification-audiences.edit',
                params: ['type' => $type],
            ),
        ];
    }
}
