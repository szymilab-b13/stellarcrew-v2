<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use DateTimeImmutable;
use Modules\Shared\Kernel\AgentId;

final readonly class UserAgentConversationView
{
    public function __construct(
        public AgentId $agentId,
        public string $agentName,
        public string $agentSlug,
        public string $agentRole,
        public ?string $lastMessagePreview,
        public ?DateTimeImmutable $lastMessageAt,
        public int $threadCount,
        public bool $isNew,
        public string $agentColor,
    ) {
    }
}
