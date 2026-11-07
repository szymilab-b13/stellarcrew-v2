<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

use Modules\Shared\Kernel\UserId;

final readonly class DeactivateUserCommand
{
    public function __construct(
        public UserId $userId,
    ) {
    }
}
