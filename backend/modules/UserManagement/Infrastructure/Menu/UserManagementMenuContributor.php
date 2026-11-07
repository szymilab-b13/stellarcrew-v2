<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;

final readonly class UserManagementMenuContributor implements MenuContributor
{
    public function contribute(AuthUser $user): array
    {
        return [
            new MenuSection(
                id: 'admin',
                labelKey: 'menu.sections.admin',
                order: 90,
                items: [
                    new MenuItem(
                        id: 'admin.users',
                        labelKey: 'menu.items.users',
                        icon: 'Users',
                        path: '/admin/users',
                        order: 10,
                        requiresRole: 'admin',
                    ),
                ],
            ),
        ];
    }
}
