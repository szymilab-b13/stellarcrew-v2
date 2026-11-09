<?php

declare(strict_types=1);

namespace Modules\Projects\Application\Query;

use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;

final readonly class GetProjectQuery
{
    public function __construct(
        public UserId $actorId,
        public ProjectId $projectId,
    ) {
    }
}
