<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\UserId;

interface ChatSessionRepository
{
    public function save(ChatSession $session): void;

    public function findById(ChatSessionId $id): ?ChatSession;

    public function findLatestActiveFor(UserId $ownerUserId): ?ChatSession;
}
