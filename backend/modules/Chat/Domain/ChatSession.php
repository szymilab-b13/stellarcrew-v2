<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function mb_strlen;

use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

use function sprintf;
use function trim;

class ChatSession
{
    public const int LABEL_MAX = 120;

    public function __construct(
        private readonly ChatSessionId $id,
        private readonly OrganizationId $organizationId,
        private readonly UserId $ownerUserId,
        private string $label,
        private readonly DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $endedAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->guardLabel($label);
    }

    public static function create(
        ChatSessionId $id,
        OrganizationId $organizationId,
        UserId $ownerUserId,
        string $label,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $organizationId,
            $ownerUserId,
            $label,
            $now,
            null,
            $now,
            $now,
        );
    }

    public function id(): ChatSessionId
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function ownerUserId(): UserId
    {
        return $this->ownerUserId;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isEnded(): bool
    {
        return $this->endedAt instanceof DateTimeImmutable;
    }

    public function rename(string $label, DateTimeImmutable $now): void
    {
        $this->guardLabel($label);

        $this->label = $label;
        $this->updatedAt = $now;
    }

    public function end(DateTimeImmutable $now): void
    {
        if ($this->endedAt instanceof DateTimeImmutable) {
            return;
        }

        $this->endedAt = $now;
        $this->updatedAt = $now;
    }

    private function guardLabel(string $label): void
    {
        $trimmed = trim($label);
        if ($trimmed === '') {
            throw new InvalidArgumentException('Chat session label must not be blank.');
        }

        if (mb_strlen($label) > self::LABEL_MAX) {
            throw new InvalidArgumentException(sprintf(
                'Chat session label must be at most %d characters.',
                self::LABEL_MAX,
            ));
        }
    }
}
