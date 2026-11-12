<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class DoctrineOrganizationRepository implements OrganizationRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Organization $organization): void
    {
        $this->em->persist($organization);
        $this->em->flush();
    }

    public function findById(OrganizationId $id): ?Organization
    {
        return $this->em->getRepository(Organization::class)->findOneBy(['id' => $id]);
    }

    public function findByOwner(UserId $ownerUserId): ?Organization
    {
        $row = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->where('o.ownerUserId = :ownerUserId')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('ownerUserId', $ownerUserId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        /** @var Organization|null $row */
        return $row;
    }

    public function findBySlug(string $slug): ?Organization
    {
        $row = $this->em->createQueryBuilder()
            ->select('o')
            ->from(Organization::class, 'o')
            ->where('o.slug = :slug')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        /** @var Organization|null $row */
        return $row;
    }

    public function existsSlug(string $slug): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Organization::class, 'o')
            ->where('o.slug = :slug')
            ->andWhere('o.deletedAt IS NULL')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
