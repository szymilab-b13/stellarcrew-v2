<?php

declare(strict_types=1);

namespace Modules\Projects\Application;

use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;

final readonly class UpdateProjectCommand
{
    public function __construct(
        public UserId $actorId,
        public ProjectId $projectId,
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
    ) {
    }
}
