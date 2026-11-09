<?php

declare(strict_types=1);

namespace Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\ProjectId;

final readonly class ProjectUpdated
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function __construct(
        public ProjectId $projectId,
        public array $before,
        public array $after,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
