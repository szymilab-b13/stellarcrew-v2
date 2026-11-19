<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

final readonly class RevokeWorkerCommand
{
    public function __construct(
        public WorkerId $workerId,
        public UserId $actorUserId,
    ) {
    }
}
