<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application\Query;

use Modules\Shared\Kernel\UserId;

final readonly class ListVisibleUsersQuery
{
    public function __construct(
        public UserId $viewerId,
        public ?string $q = null,
    ) {
    }
}
