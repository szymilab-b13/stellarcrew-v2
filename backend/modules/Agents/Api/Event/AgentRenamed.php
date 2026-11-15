<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;

final readonly class AgentRenamed
{
    public function __construct(
        public AgentId $agentId,
        public string $oldName,
        public string $newName,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
