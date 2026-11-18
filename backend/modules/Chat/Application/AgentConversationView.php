<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use DateTimeImmutable;
use Modules\Shared\Kernel\ChatThreadId;

final readonly class AgentConversationView
{
    public function __construct(
        public ChatThreadId $threadId,
        public string $subject,
        public ?string $shortDescription,
        public int $messageCount,
        public ?DateTimeImmutable $lastMessageAt,
        public ?ChatThreadId $parentThreadId,
    ) {
    }
}
