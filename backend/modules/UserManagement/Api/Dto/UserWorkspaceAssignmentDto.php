<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Dto;

use DateTimeImmutable;

final readonly class UserWorkspaceAssignmentDto
{
    public function __construct(
        public string $workspaceId,
        public string $workspaceName,
        public string $workspaceSlug,
        public string $iconColor,
        public string $iconInitials,
        public string $role,
        public DateTimeImmutable $joinedAt,
    ) {
    }
}
