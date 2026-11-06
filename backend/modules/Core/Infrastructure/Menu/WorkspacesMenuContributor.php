<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;

/**
 * Placeholder contributor. Will move to Modules\Workspaces when that module exists.
 */
final readonly class WorkspacesMenuContributor implements MenuContributor
{
    public function contribute(AuthUser $user): array
    {
        return [
            new MenuSection(
                id: 'workspaces',
                labelKey: 'menu.sections.workspaces',
                order: 20,
                items: [
                    new MenuItem(
                        id: 'workspaces.list',
                        labelKey: 'menu.items.workspaces',
                        icon: 'Building2',
                        path: '/workspaces',
                        order: 10,
                    ),
                ],
            ),
        ];
    }
}
