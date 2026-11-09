<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;

final readonly class RestoreProjectCommand
{
    public function __construct(
        public UserId $actorId,
        public ProjectId $projectId,
    ) {
    }
}
