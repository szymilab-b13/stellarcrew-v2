<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;

final readonly class CoreMenuContributor implements MenuContributor
{
    public function contribute(AuthUser $user): array
    {
        return [
            new MenuSection(
                id: 'overview',
                labelKey: 'menu.sections.overview',
                order: 10,
                items: [
                    new MenuItem(
                        id: 'dashboard',
                        labelKey: 'menu.items.dashboard',
                        icon: 'LayoutDashboard',
                        path: '/dashboard',
                        order: 10,
                    ),
                ],
            ),
        ];
    }
}
