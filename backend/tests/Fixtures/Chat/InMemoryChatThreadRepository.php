<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use function count;

use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;

final class InMemoryChatThreadRepository implements ChatThreadRepository
{
    /** @var array<string, ChatThread> keyed by thread id */
    private array $threads = [];

    public function save(ChatThread $thread): void
    {
        $this->threads[(string) $thread->id()] = $thread;
    }

    public function findById(ChatThreadId $id): ?ChatThread
    {
        return $this->threads[(string) $id] ?? null;
    }

    public function findBySession(ChatSessionId $sessionId): array
    {
        $result = [];
        foreach ($this->threads as $thread) {
            if (!$thread->sessionId()->equals($sessionId)) {
                continue;
            }

            $result[] = $thread;
        }

        return $result;
    }

    public function findChildrenOf(ChatThreadId $parentThreadId): array
    {
        $result = [];
        foreach ($this->threads as $thread) {
            $parent = $thread->parentThreadId();
            if ($parent === null) {
                continue;
            }

            if (!$parent->equals($parentThreadId)) {
                continue;
            }

            $result[] = $thread;
        }

        return $result;
    }

    public function findRootsBySession(ChatSessionId $sessionId): array
    {
        $result = [];
        foreach ($this->threads as $thread) {
            if (!$thread->sessionId()->equals($sessionId)) {
                continue;
            }

            if ($thread->parentThreadId() !== null) {
                continue;
            }

            $result[] = $thread;
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->threads);
    }
}
