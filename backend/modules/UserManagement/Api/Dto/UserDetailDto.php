<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Dto;

use DateTimeImmutable;

final readonly class UserDetailDto
{
    /**
     * @param list<UserWorkspaceAssignmentDto> $workspaceAssignments
     */
    public function __construct(
        public string $id,
        public string $email,
        public bool $isSuperAdmin,
        public ?string $displayName,
        public string $avatarColor,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $deactivatedAt,
        public array $workspaceAssignments,
    ) {
    }
}
