<?php

declare(strict_types=1);

namespace Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class ProjectCreated
{
    public function __construct(
        public ProjectId $projectId,
        public WorkspaceId $workspaceId,
        public string $name,
        public string $slug,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
