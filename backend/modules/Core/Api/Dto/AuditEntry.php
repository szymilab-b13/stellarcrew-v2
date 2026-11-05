<?php

declare(strict_types=1);

namespace Modules\Core\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

final readonly class AuditEntry
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public ?UserId $userId,
        public string $action,
        public DateTimeImmutable $occurredAt,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public array $metadata = [],
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
    }
}
