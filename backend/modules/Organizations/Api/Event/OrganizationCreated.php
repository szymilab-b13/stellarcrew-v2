<?php

declare(strict_types=1);

namespace Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class OrganizationCreated
{
    public function __construct(
        public OrganizationId $organizationId,
        public UserId $ownerUserId,
        public string $name,
        public string $slug,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
