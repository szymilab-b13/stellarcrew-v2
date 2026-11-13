<?php

declare(strict_types=1);

namespace Modules\Agents\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;
use Symfony\Component\Uid\Uuid;

class AgentMember
{
    public function __construct(
        private readonly Uuid $id,
        private readonly AgentId $agentId,
        private readonly UserId $userId,
        private readonly UserId $grantedBy,
        private readonly DateTimeImmutable $grantedAt,
    ) {
    }

    public static function grant(
        AgentId $agentId,
        UserId $userId,
        UserId $grantedBy,
        DateTimeImmutable $grantedAt,
    ): self {
        return new self(Uuid::v7(), $agentId, $userId, $grantedBy, $grantedAt);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function agentId(): AgentId
    {
        return $this->agentId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function grantedBy(): UserId
    {
        return $this->grantedBy;
    }

    public function grantedAt(): DateTimeImmutable
    {
        return $this->grantedAt;
    }
}
