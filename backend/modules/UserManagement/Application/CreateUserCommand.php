<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

use Modules\Shared\Kernel\UserId;

final readonly class CreateUserCommand
{
    public function __construct(
        public UserId $actorUserId,
        public string $email,
        public string $plaintextPassword,
        public bool $isSuperAdmin = false,
        public ?string $displayName = null,
    ) {
    }
}
