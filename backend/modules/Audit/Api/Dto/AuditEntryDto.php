<?php

declare(strict_types=1);

namespace Modules\Audit\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

final readonly class AuditEntryDto
{
    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     * @param array<string, mixed>      $metadata
     */
    public function __construct(
        public AuditEntryId $id,
        public UserId $actorUserId,
        public ?string $actorEmail,
        public string $action,
        public ?string $resourceType,
        public ?string $resourceId,
        public ?array $beforeState,
        public ?array $afterState,
        public array $metadata,
        public DateTimeImmutable $occurredAt,
        public ?DateTimeImmutable $undoneAt,
        public ?UserId $undoneBy,
        public ?string $undoneByEmail,
        public ?AuditEntryId $revertsEntryId,
    ) {
    }
}
