<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Workers\Domain\WorkerRefreshToken;
use Modules\Workers\Domain\WorkerRefreshTokenRepository;

final readonly class DoctrineWorkerRefreshTokenRepository implements WorkerRefreshTokenRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(WorkerRefreshToken $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    public function findByTokenHash(string $tokenHash): ?WorkerRefreshToken
    {
        return $this->em->getRepository(WorkerRefreshToken::class)->findOneBy(['tokenHash' => $tokenHash]);
    }
}
