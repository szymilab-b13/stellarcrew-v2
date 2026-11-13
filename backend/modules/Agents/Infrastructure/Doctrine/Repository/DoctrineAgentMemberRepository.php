<?php

declare(strict_types=1);

namespace Modules\Agents\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Modules\Agents\Domain\AgentMember;
use Modules\Agents\Domain\AgentMemberRepository;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;

final readonly class DoctrineAgentMemberRepository implements AgentMemberRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(AgentMember $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    public function find(AgentId $agentId, UserId $userId): ?AgentMember
    {
        $rows = $this->em->createQueryBuilder()
            ->select('m')
            ->from(AgentMember::class, 'm')
            ->where('m.agentId = :agentId')
            ->andWhere('m.userId = :userId')
            ->setParameter('agentId', $agentId)
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        /** @var list<AgentMember> $rows */
        return $rows[0] ?? null;
    }

    public function remove(AgentMember $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    public function exists(AgentId $agentId, UserId $userId): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(AgentMember::class, 'm')
            ->where('m.agentId = :agentId')
            ->andWhere('m.userId = :userId')
            ->setParameter('agentId', $agentId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function listMembersOf(AgentId $agentId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('m')
            ->from(AgentMember::class, 'm')
            ->where('m.agentId = :agentId')
            ->orderBy('m.grantedAt', 'ASC')
            ->setParameter('agentId', $agentId)
            ->getQuery()
            ->getResult();

        /** @var list<AgentMember> $rows */
        return $rows;
    }

    public function listAgentIdsFor(UserId $userId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('m.agentId')
            ->from(AgentMember::class, 'm')
            ->where('m.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        /** @var list<array{agentId: AgentId}> $rows */
        return array_map(static fn (array $row): AgentId => $row['agentId'], $rows);
    }
}
