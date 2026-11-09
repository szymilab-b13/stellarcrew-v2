<?php

declare(strict_types=1);

namespace Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\ProjectId;

final readonly class ProjectRestored
{
    public function __construct(
        public ProjectId $projectId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
