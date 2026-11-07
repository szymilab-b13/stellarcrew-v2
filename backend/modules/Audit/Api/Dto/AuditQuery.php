<?php

declare(strict_types=1);

namespace Modules\Audit\Api\Dto;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;

final readonly class AuditQuery
{
    public function __construct(
        public ?UserId $actorUserId = null,
        public ?string $action = null,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public bool $includeUndone = true,
        public bool $onlyReversible = false,
        public int $page = 1,
        public int $perPage = 50,
    ) {
    }
}
