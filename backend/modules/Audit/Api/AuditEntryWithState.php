<?php

declare(strict_types=1);

namespace Modules\Audit\Api;

use DateTimeImmutable;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

/**
 * Richer entry carrying before/after snapshots on top of Core's AuditEntry contract.
 *
 * Consumers that need undo must persist before/after state; this DTO is what the
 * concrete AuditApi::log accepts from module consumers.
 */
final readonly class AuditEntryWithState
{
    /**
     * @param array<string, mixed>|null $beforeState
     * @param array<string, mixed>|null $afterState
     * @param array<string, mixed>      $metadata
     */
    public function __construct(
        public UserId $actorUserId,
        public string $action,
        public DateTimeImmutable $occurredAt,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public ?array $beforeState = null,
        public ?array $afterState = null,
        public array $metadata = [],
        public ?AuditEntryId $revertsEntryId = null,
    ) {
    }
}
