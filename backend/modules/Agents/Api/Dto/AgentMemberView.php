<?php

declare(strict_types=1);

namespace Modules\Agents\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

final readonly class AgentMemberView
{
    public function __construct(
        public AgentId $agentId,
        public UserId $userId,
        public UserId $grantedBy,
        public DateTimeImmutable $grantedAt,
    ) {
    }
}
