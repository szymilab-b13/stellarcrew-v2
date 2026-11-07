<?php

declare(strict_types=1);

namespace Modules\UserManagement\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

final readonly class DoctrineUserProfileRepository implements UserProfileRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(UserProfile $profile): void
    {
        $this->em->persist($profile);
        $this->em->flush();
    }

    public function findById(UserId $id): ?UserProfile
    {
        return $this->em->getRepository(UserProfile::class)->findOneBy(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(UserProfile::class, 'p')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
