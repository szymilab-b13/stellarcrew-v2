<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use function array_values;

use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Chat\Domain\ChatThreadWorkerSessionRepository;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;

use function sprintf;

use Symfony\Component\Uid\Uuid;

final class InMemoryChatThreadWorkerSessionRepository implements ChatThreadWorkerSessionRepository
{
    /** @var array<string, ChatThreadWorkerSession> */
    private array $sessions = [];

    public function save(ChatThreadWorkerSession $session): void
    {
        $this->sessions[$this->key($session->threadId(), $session->workerId())] = $session;
    }

    public function findByThreadAndWorker(ChatThreadId $threadId, WorkerId $workerId): ?ChatThreadWorkerSession
    {
        return $this->sessions[$this->key($threadId, $workerId)] ?? null;
    }

    public function findByClaudeSessionId(Uuid $claudeSessionId): ?ChatThreadWorkerSession
    {
        foreach ($this->sessions as $session) {
            if ($session->claudeSessionId()->equals($claudeSessionId)) {
                return $session;
            }
        }

        return null;
    }

    public function deleteByWorker(WorkerId $workerId): void
    {
        foreach ($this->sessions as $key => $session) {
            if ($session->workerId()->equals($workerId)) {
                unset($this->sessions[$key]);
            }
        }
    }

    /**
     * @return list<ChatThreadWorkerSession>
     */
    public function all(): array
    {
        return array_values($this->sessions);
    }

    private function key(ChatThreadId $threadId, WorkerId $workerId): string
    {
        return sprintf('%s:%s', (string) $threadId, (string) $workerId);
    }
}
