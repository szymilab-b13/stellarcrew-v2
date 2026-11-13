<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;

final readonly class AgentDeleted
{
    public function __construct(
        public AgentId $agentId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
