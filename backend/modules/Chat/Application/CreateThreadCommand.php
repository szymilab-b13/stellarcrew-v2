<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\UserId;

final readonly class CreateThreadCommand
{
    public function __construct(
        public UserId $actorId,
        public ChatSessionId $sessionId,
        public string $subject,
        public ?ChatThreadId $parentThreadId = null,
    ) {
    }
}
