<?php

declare(strict_types=1);

namespace Modules\Audit\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

final class AuditLogEntry
{
    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     * @param array<string, mixed>      $metadata
     */
    public function __construct(
        private readonly AuditEntryId $id,
        private readonly UserId $actorUserId,
        private readonly string $action,
        private readonly DateTimeImmutable $occurredAt,
        private readonly ?string $resourceType = null,
        private readonly ?string $resourceId = null,
        private readonly ?array $beforeState = null,
        private readonly ?array $afterState = null,
        private readonly array $metadata = [],
        private ?DateTimeImmutable $undoneAt = null,
        private ?UserId $undoneBy = null,
        private readonly ?AuditEntryId $revertsEntryId = null,
    ) {
    }

    public function id(): AuditEntryId
    {
        return $this->id;
    }

    public function actorUserId(): UserId
    {
        return $this->actorUserId;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function resourceType(): ?string
    {
        return $this->resourceType;
    }

    public function resourceId(): ?string
    {
        return $this->resourceId;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function beforeState(): ?array
    {
        return $this->beforeState;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function afterState(): ?array
    {
        return $this->afterState;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function undoneAt(): ?DateTimeImmutable
    {
        return $this->undoneAt;
    }

    public function undoneBy(): ?UserId
    {
        return $this->undoneBy;
    }

    public function revertsEntryId(): ?AuditEntryId
    {
        return $this->revertsEntryId;
    }

    public function markUndone(DateTimeImmutable $at, UserId $by): void
    {
        $this->undoneAt = $at;
        $this->undoneBy = $by;
    }
}
