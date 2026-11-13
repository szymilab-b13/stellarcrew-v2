<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Shared\Kernel\UserId;

final readonly class ListAgentsQuery
{
    public function __construct(
        public UserId $actorId,
    ) {
    }
}
