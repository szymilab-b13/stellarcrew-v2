<?php

declare(strict_types=1);

namespace Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\OrganizationId;

final readonly class OrganizationRestored
{
    public function __construct(
        public OrganizationId $organizationId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
