<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class AgentCreated
{
    public function __construct(
        public AgentId $agentId,
        public OrganizationId $organizationId,
        public UserId $ownerUserId,
        public string $name,
        public string $slug,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
