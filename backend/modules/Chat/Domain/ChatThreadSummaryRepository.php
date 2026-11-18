<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use Modules\Shared\Kernel\ChatThreadId;

interface ChatThreadSummaryRepository
{
    public function save(ChatThreadSummary $summary): void;

    public function findByThreadId(ChatThreadId $threadId): ?ChatThreadSummary;
}
