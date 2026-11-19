<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use Symfony\Component\Uid\Uuid;

final readonly class RecordAgentReplyCommand
{
    public function __construct(
        public ChatThreadId $threadId,
        public WorkerId $workerId,
        public Uuid $agentId,
        public string $content,
        public ?ChatMessageId $replyToMessageId = null,
    ) {
    }
}
