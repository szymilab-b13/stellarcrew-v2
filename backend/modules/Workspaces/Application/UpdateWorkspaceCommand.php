<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class UpdateWorkspaceCommand
{
    public function __construct(
        public WorkspaceId $workspaceId,
        public UserId $actorId,
        public ?string $name = null,
        public ?string $iconColor = null,
        public ?string $iconInitials = null,
    ) {
    }
}
