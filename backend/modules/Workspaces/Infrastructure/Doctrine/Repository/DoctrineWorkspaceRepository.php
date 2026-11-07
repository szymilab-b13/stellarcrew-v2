<?php

declare(strict_types=1);

namespace Modules\Workspaces\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceMember;
use Modules\Workspaces\Domain\WorkspaceRepository;
use Modules\Workspaces\Domain\WorkspaceRole;

final readonly class DoctrineWorkspaceRepository implements WorkspaceRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function saveWorkspace(Workspace $workspace): void
    {
        $this->em->persist($workspace);
        $this->em->flush();
    }

    public function findById(WorkspaceId $id): ?Workspace
    {
        return $this->em->getRepository(Workspace::class)->findOneBy(['id' => $id]);
    }

    public function findBySlug(string $slug): ?Workspace
    {
        return $this->em->getRepository(Workspace::class)->findOneBy(['slug' => $slug]);
    }

    public function saveMembership(WorkspaceMember $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    public function removeMembership(WorkspaceMember $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    public function removeAllMembershipsOf(UserId $userId): void
    {
        $members = $this->em->getRepository(WorkspaceMember::class)->findBy(['userId' => $userId]);
        foreach ($members as $member) {
            $this->em->remove($member);
        }

        $this->em->flush();
    }

    public function findMembership(WorkspaceId $workspaceId, UserId $userId): ?WorkspaceMember
    {
        return $this->em->getRepository(WorkspaceMember::class)->findOneBy([
            'workspaceId' => $workspaceId,
            'userId' => $userId,
        ]);
    }

    public function listForUser(UserId $userId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('w')
            ->from(Workspace::class, 'w')
            ->innerJoin(WorkspaceMember::class, 'm', 'WITH', 'm.workspaceId = w.id')
            ->where('m.userId = :userId')
            ->andWhere('w.deletedAt IS NULL')
            ->orderBy('w.createdAt', 'ASC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        /** @var list<Workspace> $rows */
        return $rows;
    }

    public function listForUserWithRoles(UserId $userId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('w', 'm.role AS role')
            ->from(Workspace::class, 'w')
            ->innerJoin(WorkspaceMember::class, 'm', 'WITH', 'm.workspaceId = w.id')
            ->where('m.userId = :userId')
            ->andWhere('w.deletedAt IS NULL')
            ->orderBy('w.createdAt', 'ASC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        /** @var list<array{0: Workspace, role: WorkspaceRole|string}> $rows */
        $result = [];
        foreach ($rows as $row) {
            $workspace = $row[0];
            $role = $row['role'];
            $result[] = [
                'workspace' => $workspace,
                'role' => $role instanceof WorkspaceRole ? $role : WorkspaceRole::from((string) $role),
            ];
        }

        return $result;
    }

    public function count(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(w.id)')
            ->from(Workspace::class, 'w')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
