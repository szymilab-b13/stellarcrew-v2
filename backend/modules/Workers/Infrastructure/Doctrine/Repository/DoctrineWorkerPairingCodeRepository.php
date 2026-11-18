<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Domain\WorkerPairingCodeRepository;

final readonly class DoctrineWorkerPairingCodeRepository implements WorkerPairingCodeRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(WorkerPairingCode $code): void
    {
        $this->em->persist($code);
        $this->em->flush();
    }

    public function findByCode(string $code): ?WorkerPairingCode
    {
        return $this->em->getRepository(WorkerPairingCode::class)->findOneBy(['code' => $code]);
    }
}
