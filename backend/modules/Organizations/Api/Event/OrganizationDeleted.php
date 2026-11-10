<?php

declare(strict_types=1);

namespace Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\OrganizationId;

final readonly class OrganizationDeleted
{
    public function __construct(
        public OrganizationId $organizationId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
