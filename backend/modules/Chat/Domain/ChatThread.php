<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;

use function sprintf;
use function trim;

class ChatThread
{
    public const int SUBJECT_MAX = 120;

    public function __construct(
        private readonly ChatThreadId $id,
        private readonly ChatSessionId $sessionId,
        private string $subject,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?ChatThreadId $parentThreadId = null,
        private ?WorkerId $currentWorkerId = null,
    ) {
        $this->guardSubject($subject);
    }

    public static function create(
        ChatThreadId $id,
        ChatSessionId $sessionId,
        string $subject,
        DateTimeImmutable $now,
        ?ChatThreadId $parentThreadId = null,
    ): self {
        return new self(
            $id,
            $sessionId,
            $subject,
            $now,
            $now,
            $parentThreadId,
        );
    }

    public function id(): ChatThreadId
    {
        return $this->id;
    }

    public function sessionId(): ChatSessionId
    {
        return $this->sessionId;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function parentThreadId(): ?ChatThreadId
    {
        return $this->parentThreadId;
    }

    public function currentWorkerId(): ?WorkerId
    {
        return $this->currentWorkerId;
    }

    public function assignCurrentWorker(?WorkerId $workerId, DateTimeImmutable $now): void
    {
        if ($this->currentWorkerId === null && $workerId === null) {
            return;
        }

        if ($this->currentWorkerId !== null && $workerId !== null && $this->currentWorkerId->equals($workerId)) {
            return;
        }

        $this->currentWorkerId = $workerId;
        $this->updatedAt = $now;
    }

    public function rename(string $subject, DateTimeImmutable $now): void
    {
        $this->guardSubject($subject);

        $this->subject = $subject;
        $this->updatedAt = $now;
    }

    public function attachToParent(ChatThreadId $parentThreadId, DateTimeImmutable $now): void
    {
        if ($parentThreadId->equals($this->id)) {
            throw new InvalidArgumentException('Chat thread cannot be attached to itself.');
        }

        $this->parentThreadId = $parentThreadId;
        $this->updatedAt = $now;
    }

    public function detachFromParent(DateTimeImmutable $now): void
    {
        $this->parentThreadId = null;
        $this->updatedAt = $now;
    }

    private function guardSubject(string $subject): void
    {
        $trimmed = trim($subject);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Chat thread subject must not be blank.');
        }

        if (mb_strlen($subject) > self::SUBJECT_MAX) {
            throw new InvalidArgumentException(sprintf(
                'Chat thread subject must be at most %d characters.',
                self::SUBJECT_MAX,
            ));
        }
    }
}
