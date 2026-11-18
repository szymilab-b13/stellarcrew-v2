<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\UserId;

final readonly class DetachThreadCommand
{
    public function __construct(
        public UserId $actorId,
        public ChatThreadId $threadId,
    ) {
    }
}
