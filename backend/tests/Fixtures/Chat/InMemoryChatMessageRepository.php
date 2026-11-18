<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Chat;

use function array_slice;
use function array_values;
use function count;

use DateTimeImmutable;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Symfony\Component\Uid\Uuid;

use function usort;

final class InMemoryChatMessageRepository implements ChatMessageRepository
{
    /** @var array<string, ChatMessage> keyed by message id */
    private array $messages = [];

    public function save(ChatMessage $message): void
    {
        $this->messages[(string) $message->id()] = $message;
    }

    public function findById(ChatMessageId $id): ?ChatMessage
    {
        return $this->messages[(string) $id] ?? null;
    }

    public function findByThread(ChatThreadId $threadId): array
    {
        $result = [];
        foreach ($this->messages as $message) {
            if (!$message->threadId()->equals($threadId)) {
                continue;
            }

            $result[] = $message;
        }

        usort(
            $result,
            static fn (ChatMessage $left, ChatMessage $right): int => $left->createdAt() <=> $right->createdAt(),
        );

        return $result;
    }

    public function findByThreadPaginated(ChatThreadId $threadId, int $limit, int $offset): array
    {
        return array_slice($this->findByThread($threadId), $offset, $limit);
    }

    public function countByThread(ChatThreadId $threadId): int
    {
        return count($this->findByThread($threadId));
    }

    public function findDistinctThreadIdsByParticipant(Uuid $participantId): array
    {
        $byId = [];
        foreach ($this->messages as $message) {
            if (!$message->senderId()->equals($participantId) && !$message->recipientId()->equals($participantId)) {
                continue;
            }

            $byId[(string) $message->threadId()] = $message->threadId();
        }

        return array_values($byId);
    }

    public function latestCreatedAtByThread(ChatThreadId $threadId): ?DateTimeImmutable
    {
        $latest = null;
        foreach ($this->messages as $message) {
            if (!$message->threadId()->equals($threadId)) {
                continue;
            }

            if ($latest === null || $message->createdAt() > $latest) {
                $latest = $message->createdAt();
            }
        }

        return $latest;
    }

    public function count(): int
    {
        return count($this->messages);
    }
}
