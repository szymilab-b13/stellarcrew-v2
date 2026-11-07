<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Dto;

use DateTimeImmutable;

final readonly class UserListItemDto
{
    public function __construct(
        public string $id,
        public string $email,
        public bool $isSuperAdmin,
        public int $workspaceCount,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $deactivatedAt,
    ) {
    }
}
