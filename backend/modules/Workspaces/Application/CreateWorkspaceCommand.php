<?php

declare(strict_types=1);

namespace Modules\Workspaces\Application;

use Modules\Shared\Kernel\UserId;

final readonly class CreateWorkspaceCommand
{
    public function __construct(
        public UserId $ownerId,
        public string $name,
        public ?string $iconColor = null,
        public ?string $iconInitials = null,
    ) {
    }
}
