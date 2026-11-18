<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatMessage;

final readonly class ListMessagesResult
{
    /**
     * @param list<ChatMessage> $messages
     */
    public function __construct(
        public array $messages,
        public int $total,
        public int $limit,
        public int $offset,
    ) {
    }
}
