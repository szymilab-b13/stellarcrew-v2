<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use Symfony\Component\Uid\Uuid;

/**
 * Event published after a user message lands in a thread that already has an
 * assigned worker. Consumers are Node worker processes that spawn
 * `claude -p --resume $claudeSessionId` and record the reply via
 * POST /api/workers/me/messages.
 *
 * Routed through `chat_agent_replies` transport (AMQP direct exchange
 * `chat.agent_replies`); the routing key is the target worker id.
 */
final readonly class AgentReplyRequested
{
    public function __construct(
        public ChatThreadId $threadId,
        public WorkerId $workerId,
        public Uuid $agentId,
        public ChatMessageId $userMessageId,
        public string $userContent,
        public string $subject,
    ) {
    }
}
