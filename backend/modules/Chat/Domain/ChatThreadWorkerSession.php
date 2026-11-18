<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use Symfony\Component\Uid\Uuid;

class ChatThreadWorkerSession
{
    public function __construct(
        private readonly ChatThreadId $threadId,
        private readonly WorkerId $workerId,
        private readonly Uuid $claudeSessionId,
        private ?DateTimeImmutable $lastMessageAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function start(
        ChatThreadId $threadId,
        WorkerId $workerId,
        Uuid $claudeSessionId,
        DateTimeImmutable $now,
    ): self {
        return new self($threadId, $workerId, $claudeSessionId, null, $now);
    }

    public function threadId(): ChatThreadId
    {
        return $this->threadId;
    }

    public function workerId(): WorkerId
    {
        return $this->workerId;
    }

    public function claudeSessionId(): Uuid
    {
        return $this->claudeSessionId;
    }

    public function lastMessageAt(): ?DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function recordMessageAt(DateTimeImmutable $now): void
    {
        $this->lastMessageAt = $now;
    }
}
