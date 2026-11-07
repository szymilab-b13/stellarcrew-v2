<?php

declare(strict_types=1);

namespace Modules\UserManagement\Api\Dto;

final readonly class UserProfileDto
{
    public function __construct(
        public string $id,
        public ?string $displayName,
        public string $avatarColor,
        public bool $isDeactivated,
    ) {
    }
}
