<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use Symfony\Component\Uid\Uuid;

interface ChatThreadWorkerSessionRepository
{
    public function save(ChatThreadWorkerSession $session): void;

    public function findByThreadAndWorker(ChatThreadId $threadId, WorkerId $workerId): ?ChatThreadWorkerSession;

    public function findByClaudeSessionId(Uuid $claudeSessionId): ?ChatThreadWorkerSession;

    public function deleteByWorker(WorkerId $workerId): void;
}
