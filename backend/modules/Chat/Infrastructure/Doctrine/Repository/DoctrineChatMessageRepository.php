<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Doctrine\Repository;

use function array_map;
use function array_values;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineChatMessageRepository implements ChatMessageRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(ChatMessage $message): void
    {
        $this->em->persist($message);
        $this->em->flush();
    }

    public function findById(ChatMessageId $id): ?ChatMessage
    {
        return $this->em->getRepository(ChatMessage::class)->findOneBy(['id' => $id]);
    }

    public function findByThread(ChatThreadId $threadId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('m')
            ->from(ChatMessage::class, 'm')
            ->where('m.threadId = :threadId')
            ->orderBy('m.createdAt', 'ASC')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->getResult();

        /** @var list<ChatMessage> $rows */
        return $rows;
    }

    public function findByThreadPaginated(ChatThreadId $threadId, int $limit, int $offset): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('m')
            ->from(ChatMessage::class, 'm')
            ->where('m.threadId = :threadId')
            ->orderBy('m.createdAt', 'ASC')
            ->setParameter('threadId', $threadId)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        /** @var list<ChatMessage> $rows */
        return $rows;
    }

    public function countByThread(ChatThreadId $threadId): int
    {
        $count = $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(ChatMessage::class, 'm')
            ->where('m.threadId = :threadId')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }

    public function findDistinctThreadIdsByParticipant(Uuid $participantId): array
    {
        /** @var list<array{threadId: ChatThreadId}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('DISTINCT m.threadId AS threadId')
            ->from(ChatMessage::class, 'm')
            ->where('m.senderId = :participantId OR m.recipientId = :participantId')
            ->setParameter('participantId', $participantId)
            ->getQuery()
            ->getResult();

        return array_values(array_map(static fn (array $row): ChatThreadId => $row['threadId'], $rows));
    }

    public function latestCreatedAtByThread(ChatThreadId $threadId): ?DateTimeImmutable
    {
        $result = $this->em->createQueryBuilder()
            ->select('MAX(m.createdAt)')
            ->from(ChatMessage::class, 'm')
            ->where('m.threadId = :threadId')
            ->setParameter('threadId', $threadId)
            ->getQuery()
            ->getSingleScalarResult();

        if ($result === null) {
            return null;
        }

        return new DateTimeImmutable((string) $result);
    }
}
