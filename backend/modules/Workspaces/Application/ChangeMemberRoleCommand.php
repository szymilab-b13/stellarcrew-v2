<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class ChangeMemberRoleCommand
{
    public function __construct(
        public WorkspaceId $workspaceId,
        public UserId $userId,
        public WorkspaceRole $role,
    ) {
    }
}
