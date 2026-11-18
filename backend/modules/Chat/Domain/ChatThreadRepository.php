<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;

interface ChatThreadRepository
{
    public function save(ChatThread $thread): void;

    public function findById(ChatThreadId $id): ?ChatThread;

    /**
     * @return list<ChatThread>
     */
    public function findBySession(ChatSessionId $sessionId): array;

    /**
     * @return list<ChatThread>
     */
    public function findChildrenOf(ChatThreadId $parentThreadId): array;

    /**
     * @return list<ChatThread>
     */
    public function findRootsBySession(ChatSessionId $sessionId): array;
}
