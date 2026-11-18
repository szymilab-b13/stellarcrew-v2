<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\UserId;
use Symfony\Component\Uid\Uuid;

final readonly class GetAgentConversationsQuery
{
    public function __construct(
        public UserId $actorId,
        public Uuid $agentId,
    ) {
    }
}
