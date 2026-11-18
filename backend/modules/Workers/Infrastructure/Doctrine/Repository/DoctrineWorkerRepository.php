<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;

final readonly class DoctrineWorkerRepository implements WorkerRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Worker $worker): void
    {
        $this->em->persist($worker);
        $this->em->flush();
    }

    public function findById(WorkerId $id): ?Worker
    {
        return $this->em->getRepository(Worker::class)->findOneBy(['id' => $id]);
    }

    public function findByRabbitmqUser(string $rabbitmqUser): ?Worker
    {
        return $this->em->getRepository(Worker::class)->findOneBy(['rabbitmqUser' => $rabbitmqUser]);
    }

    public function findByOwner(UserId $ownerUserId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('w')
            ->from(Worker::class, 'w')
            ->where('w.ownerUserId = :ownerUserId')
            ->orderBy('w.createdAt', 'ASC')
            ->setParameter('ownerUserId', $ownerUserId)
            ->getQuery()
            ->getResult();

        /** @var list<Worker> $rows */
        return $rows;
    }
}
