<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\UserId;

final readonly class ListUserAgentConversationsQuery
{
    public function __construct(
        public UserId $actorId,
    ) {
    }
}
