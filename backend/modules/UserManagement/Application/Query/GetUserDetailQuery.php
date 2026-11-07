<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\Shared\Kernel\UserId;

final readonly class GetUserDetailQuery
{
    public function __construct(
        public UserId $userId,
    ) {
    }
}
