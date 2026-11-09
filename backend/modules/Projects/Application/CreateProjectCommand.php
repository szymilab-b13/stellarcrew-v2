<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class CreateProjectCommand
{
    public function __construct(
        public UserId $actorId,
        public WorkspaceId $workspaceId,
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
    ) {
    }
}
