<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use function count;

use Modules\Chat\Domain\ChatThreadSummary;
use Modules\Chat\Domain\ChatThreadSummaryRepository;
use Modules\Shared\Kernel\ChatThreadId;

final class InMemoryChatThreadSummaryRepository implements ChatThreadSummaryRepository
{
    /** @var array<string, ChatThreadSummary> keyed by thread id */
    private array $summaries = [];

    public function save(ChatThreadSummary $summary): void
    {
        $this->summaries[(string) $summary->threadId()] = $summary;
    }

    public function findByThreadId(ChatThreadId $threadId): ?ChatThreadSummary
    {
        return $this->summaries[(string) $threadId] ?? null;
    }

    public function count(): int
    {
        return count($this->summaries);
    }
}
