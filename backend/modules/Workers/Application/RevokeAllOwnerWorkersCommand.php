<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\UserId;

final readonly class RevokeAllOwnerWorkersCommand
{
    public function __construct(
        public UserId $ownerUserId,
    ) {
    }
}
