<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Dto;

final readonly class MembershipView
{
    public function __construct(
        public string $workspaceId,
        public string $userId,
        public string $role,
    ) {
    }
}
