<?php

declare(strict_types=1);

namespace Modules\Agents\Application\Query;

use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

final readonly class GetAgentQuery
{
    public function __construct(
        public UserId $actorId,
        public AgentId $agentId,
    ) {
    }
}
