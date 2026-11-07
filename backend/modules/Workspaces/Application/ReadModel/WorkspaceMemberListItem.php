<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application\ReadModel;

use DateTimeImmutable;

final readonly class WorkspaceMemberListItem
{
    public function __construct(
        public string $userId,
        public string $email,
        public string $role,
        public DateTimeImmutable $joinedAt,
    ) {
    }
}
