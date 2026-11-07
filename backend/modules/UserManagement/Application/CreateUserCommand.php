<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $email,
        public string $plaintextPassword,
        public bool $isSuperAdmin = false,
        public ?string $displayName = null,
    ) {
    }
}
