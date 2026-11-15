<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentRepository;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;

final readonly class DoctrineAgentRepository implements AgentRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Agent $agent): void
    {
        $this->em->persist($agent);
        $this->em->flush();
    }

    public function findById(AgentId $id): ?Agent
    {
        return $this->em->getRepository(Agent::class)->findOneBy(['id' => $id]);
    }

    public function findBySlug(OrganizationId $organizationId, string $slug): ?Agent
    {
        $rows = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Agent::class, 'a')
            ->where('a.organizationId = :organizationId')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        /** @var list<Agent> $rows */
        return $rows[0] ?? null;
    }

    public function existsSlug(OrganizationId $organizationId, string $slug): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(Agent::class, 'a')
            ->where('a.organizationId = :organizationId')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('organizationId', $organizationId)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function countActiveFor(OrganizationId $organizationId): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(a.id)')
            ->from(Agent::class, 'a')
            ->where('a.organizationId = :organizationId')
            ->andWhere('a.deletedAt IS NULL')
            ->setParameter('organizationId', $organizationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Agent::class, 'a')
            ->where('a.id IN (:ids)')
            ->andWhere('a.deletedAt IS NULL')
            ->orderBy('a.name', 'ASC')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        /** @var list<Agent> $rows */
        return $rows;
    }
}
