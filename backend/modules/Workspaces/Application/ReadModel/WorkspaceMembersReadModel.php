<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application\ReadModel;

use Modules\Shared\Kernel\WorkspaceId;

interface WorkspaceMembersReadModel
{
    /**
     * @return list<WorkspaceMemberListItem>
     */
    public function listForWorkspace(WorkspaceId $workspaceId): array;
}
