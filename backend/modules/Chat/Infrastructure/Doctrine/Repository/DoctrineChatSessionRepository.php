<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\UserId;

final readonly class DoctrineChatSessionRepository implements ChatSessionRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(ChatSession $session): void
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    public function findById(ChatSessionId $id): ?ChatSession
    {
        return $this->em->getRepository(ChatSession::class)->findOneBy(['id' => $id]);
    }

    public function findLatestActiveFor(UserId $ownerUserId): ?ChatSession
    {
        /** @var ?ChatSession $result */
        $result = $this->em->createQueryBuilder()
            ->select('s')
            ->from(ChatSession::class, 's')
            ->where('s.ownerUserId = :ownerUserId')
            ->andWhere('s.endedAt IS NULL')
            ->orderBy('s.startedAt', 'DESC')
            ->setParameter('ownerUserId', $ownerUserId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }
}
