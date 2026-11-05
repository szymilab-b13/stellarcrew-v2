<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Core\Domain\User;
use Modules\Core\Domain\UserRepository;

final readonly class DoctrineUserRepository implements UserRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(User $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function count(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
