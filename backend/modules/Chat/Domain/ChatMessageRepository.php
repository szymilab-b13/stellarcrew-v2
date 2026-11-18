<?php

declare(strict_types=1);

namespace Modules\Chat\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Symfony\Component\Uid\Uuid;

interface ChatMessageRepository
{
    public function save(ChatMessage $message): void;

    public function findById(ChatMessageId $id): ?ChatMessage;

    /**
     * @return list<ChatMessage>
     */
    public function findByThread(ChatThreadId $threadId): array;

    /**
     * @return list<ChatMessage>
     */
    public function findByThreadPaginated(ChatThreadId $threadId, int $limit, int $offset): array;

    public function countByThread(ChatThreadId $threadId): int;

    /**
     * @return list<ChatThreadId>
     */
    public function findDistinctThreadIdsByParticipant(Uuid $participantId): array;

    public function latestCreatedAtByThread(ChatThreadId $threadId): ?DateTimeImmutable;
}
