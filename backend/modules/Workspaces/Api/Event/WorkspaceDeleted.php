<?php

declare(strict_types=1);

namespace Modules\Workspaces\Api\Event;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class WorkspaceDeleted
{
    public function __construct(
        public WorkspaceId $workspaceId,
        public UserId $actorId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
