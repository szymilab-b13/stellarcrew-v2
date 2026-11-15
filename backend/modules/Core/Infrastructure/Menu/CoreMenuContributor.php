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
                id: 'general',
                labelKey: 'menu.sections.general',
                order: 10,
                items: [
                    new MenuItem(
                        id: 'dashboard',
                        labelKey: 'menu.items.dashboard',
                        icon: 'LayoutDashboard',
                        path: '/dashboard',
                        order: 10,
                    ),
                    new MenuItem(
                        id: 'chat',
                        labelKey: 'menu.items.chat',
                        icon: 'MessageSquare',
                        path: '/chat',
                        order: 30,
                        disabledMilestoneKey: 'menu.workspaceContext.milestones.chat',
                    ),
                    new MenuItem(
                        id: 'agents',
                        labelKey: 'menu.items.agents',
                        icon: 'Bot',
                        path: '/agents',
                        order: 40,
                    ),
                ],
            ),
        ];
    }
}
