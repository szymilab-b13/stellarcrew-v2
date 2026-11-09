<?php

declare(strict_types=1);

namespace Modules\Projects\Application\Query;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class ListProjectsByWorkspaceQuery
{
    public function __construct(
        public UserId $actorId,
        public WorkspaceId $workspaceId,
    ) {
    }
}
