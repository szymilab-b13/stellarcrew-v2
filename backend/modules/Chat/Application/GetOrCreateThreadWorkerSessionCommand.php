<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;

final readonly class GetOrCreateThreadWorkerSessionCommand
{
    public function __construct(
        public ChatThreadId $threadId,
        public WorkerId $workerId,
    ) {
    }
}
