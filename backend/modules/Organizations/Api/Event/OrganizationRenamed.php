<?php

declare(strict_types=1);

namespace Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\OrganizationId;

final readonly class OrganizationRenamed
{
    public function __construct(
        public OrganizationId $organizationId,
        public string $oldName,
        public string $newName,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
