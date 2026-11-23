<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;

final readonly class WorkersMenuContributor implements MenuContributor
{
    public function contribute(AuthUser $user): array
    {
        return [
            new MenuSection(
                id: 'settings',
                labelKey: 'menu.sections.settings',
                order: 80,
                items: [
                    new MenuItem(
                        id: 'settings.workers',
                        labelKey: 'menu.items.workers',
                        icon: 'Server',
                        path: '/settings/workers',
                        order: 10,
                    ),
                ],
            ),
        ];
    }
}
