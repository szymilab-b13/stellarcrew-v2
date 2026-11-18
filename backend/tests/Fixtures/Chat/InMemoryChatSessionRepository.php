<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use function count;

use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\UserId;

final class InMemoryChatSessionRepository implements ChatSessionRepository
{
    /** @var array<string, ChatSession> keyed by session id */
    private array $sessions = [];

    public function save(ChatSession $session): void
    {
        $this->sessions[(string) $session->id()] = $session;
    }

    public function findById(ChatSessionId $id): ?ChatSession
    {
        return $this->sessions[(string) $id] ?? null;
    }

    public function findLatestActiveFor(UserId $ownerUserId): ?ChatSession
    {
        $latest = null;
        foreach ($this->sessions as $session) {
            if (!$session->ownerUserId()->equals($ownerUserId)) {
                continue;
            }

            if ($session->isEnded()) {
                continue;
            }

            if ($latest === null || $session->startedAt() > $latest->startedAt()) {
                $latest = $session;
            }
        }

        return $latest;
    }

    public function count(): int
    {
        return count($this->sessions);
    }
}
