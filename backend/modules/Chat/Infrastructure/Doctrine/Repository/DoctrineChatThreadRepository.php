<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;

final readonly class DoctrineChatThreadRepository implements ChatThreadRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(ChatThread $thread): void
    {
        $this->em->persist($thread);
        $this->em->flush();
    }

    public function findById(ChatThreadId $id): ?ChatThread
    {
        return $this->em->getRepository(ChatThread::class)->findOneBy(['id' => $id]);
    }

    public function findBySession(ChatSessionId $sessionId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('t')
            ->from(ChatThread::class, 't')
            ->where('t.sessionId = :sessionId')
            ->orderBy('t.createdAt', 'ASC')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getResult();

        /** @var list<ChatThread> $rows */
        return $rows;
    }

    public function findChildrenOf(ChatThreadId $parentThreadId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('t')
            ->from(ChatThread::class, 't')
            ->where('t.parentThreadId = :parentThreadId')
            ->orderBy('t.createdAt', 'ASC')
            ->setParameter('parentThreadId', $parentThreadId)
            ->getQuery()
            ->getResult();

        /** @var list<ChatThread> $rows */
        return $rows;
    }

    public function findRootsBySession(ChatSessionId $sessionId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('t')
            ->from(ChatThread::class, 't')
            ->where('t.sessionId = :sessionId')
            ->andWhere('t.parentThreadId IS NULL')
            ->orderBy('t.createdAt', 'ASC')
            ->setParameter('sessionId', $sessionId)
            ->getQuery()
            ->getResult();

        /** @var list<ChatThread> $rows */
        return $rows;
    }
}
