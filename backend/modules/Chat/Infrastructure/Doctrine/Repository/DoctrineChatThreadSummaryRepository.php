<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Chat\Domain\ChatThreadSummary;
use Modules\Chat\Domain\ChatThreadSummaryRepository;
use Modules\Shared\Kernel\ChatThreadId;

final readonly class DoctrineChatThreadSummaryRepository implements ChatThreadSummaryRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(ChatThreadSummary $summary): void
    {
        $this->em->persist($summary);
        $this->em->flush();
    }

    public function findByThreadId(ChatThreadId $threadId): ?ChatThreadSummary
    {
        return $this->em->getRepository(ChatThreadSummary::class)->findOneBy(['threadId' => $threadId]);
    }
}
