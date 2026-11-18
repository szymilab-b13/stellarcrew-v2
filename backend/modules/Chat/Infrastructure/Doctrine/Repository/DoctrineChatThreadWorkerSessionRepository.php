<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Chat\Domain\ChatThreadWorkerSessionRepository;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\WorkerId;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineChatThreadWorkerSessionRepository implements ChatThreadWorkerSessionRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(ChatThreadWorkerSession $session): void
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    public function findByThreadAndWorker(ChatThreadId $threadId, WorkerId $workerId): ?ChatThreadWorkerSession
    {
        return $this->em->getRepository(ChatThreadWorkerSession::class)->findOneBy([
            'threadId' => $threadId,
            'workerId' => $workerId,
        ]);
    }

    public function findByClaudeSessionId(Uuid $claudeSessionId): ?ChatThreadWorkerSession
    {
        return $this->em->getRepository(ChatThreadWorkerSession::class)->findOneBy([
            'claudeSessionId' => $claudeSessionId,
        ]);
    }
}
