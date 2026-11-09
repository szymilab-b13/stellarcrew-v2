<?php

declare(strict_types=1);

namespace Modules\Projects\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Projects\Domain\Project;
use Modules\Projects\Domain\ProjectRepository;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;

final readonly class DoctrineProjectRepository implements ProjectRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Project $project): void
    {
        $this->em->persist($project);
        $this->em->flush();
    }

    public function findById(ProjectId $id): ?Project
    {
        return $this->em->getRepository(Project::class)->findOneBy(['id' => $id]);
    }

    public function findByWorkspace(WorkspaceId $workspaceId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p')
            ->where('p.workspaceId = :workspaceId')
            ->andWhere('p.deletedAt IS NULL')
            ->orderBy('p.createdAt', 'ASC')
            ->setParameter('workspaceId', $workspaceId)
            ->getQuery()
            ->getResult();

        /** @var list<Project> $rows */
        return $rows;
    }

    public function existsSlugInWorkspace(WorkspaceId $workspaceId, string $slug): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Project::class, 'p')
            ->where('p.workspaceId = :workspaceId')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('workspaceId', $workspaceId)
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
