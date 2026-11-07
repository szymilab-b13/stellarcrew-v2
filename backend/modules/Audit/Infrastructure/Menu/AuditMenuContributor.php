<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Menu;

use Modules\Auth\Api\Dto\AuthUser;
use Modules\Core\Api\Dto\MenuItem;
use Modules\Core\Api\Dto\MenuSection;
use Modules\Core\Api\MenuContributor;

final readonly class AuditMenuContributor implements MenuContributor
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
                        id: 'admin.audit',
                        labelKey: 'menu.items.auditLog',
                        icon: 'ScrollText',
                        path: '/admin/audit',
                        order: 20,
                        requiresRole: 'admin',
                    ),
                ],
            ),
        ];
    }
}
