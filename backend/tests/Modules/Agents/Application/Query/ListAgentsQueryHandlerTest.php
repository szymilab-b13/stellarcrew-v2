<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application\Query;

use App\Tests\Fixtures\Agents\InMemoryAgentMemberRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Application\Query\ListAgentsQuery;
use Modules\Agents\Application\Query\ListAgentsQueryHandler;
use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentMember;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ListAgentsQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenUserHasNoMemberships(): void
    {
        $actorId = new UserId(Uuid::v7());
        $sut = new ListAgentsQueryHandler(new InMemoryAgentRepository(), new InMemoryAgentMemberRepository());

        self::assertSame([], $sut(new ListAgentsQuery($actorId)));
    }

    public function testShouldReturnOwnedAgentForOwner(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $owned = $this->seedAgent($agents, $members, $organizationId, $actorId, 'Maja', 'maja');
        $sut = new ListAgentsQueryHandler($agents, $members);

        $result = $sut(new ListAgentsQuery($actorId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->id()->equals($owned->id()));
    }

    public function testShouldReturnSharedAgentWhenUserIsNonOwnerMember(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $memberId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $shared = $this->seedAgent($agents, $members, $organizationId, $ownerId, 'Viktor', 'viktor');
        $members->save(AgentMember::grant($shared->id(), $memberId, $ownerId, $this->now()));
        $sut = new ListAgentsQueryHandler($agents, $members);

        $result = $sut(new ListAgentsQuery($memberId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->id()->equals($shared->id()));
    }

    public function testShouldReturnOwnedAndSharedMixedSortedByName(): void
    {
        $actorId = new UserId(Uuid::v7());
        $otherOwnerId = new UserId(Uuid::v7());
        $ownOrg = new OrganizationId(Uuid::v7());
        $foreignOrg = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $this->seedAgent($agents, $members, $ownOrg, $actorId, 'Viktor', 'viktor');
        $this->seedAgent($agents, $members, $ownOrg, $actorId, 'Maja', 'maja');
        $foreign = $this->seedAgent($agents, $members, $foreignOrg, $otherOwnerId, 'Hefajstos', 'hefajstos');
        $members->save(AgentMember::grant($foreign->id(), $actorId, $otherOwnerId, $this->now()));
        $sut = new ListAgentsQueryHandler($agents, $members);

        $result = $sut(new ListAgentsQuery($actorId));

        self::assertCount(3, $result);
        self::assertSame(['Hefajstos', 'Maja', 'Viktor'], array_map(static fn (Agent $a): string => $a->name(), $result));
    }

    public function testShouldExcludeSoftDeletedAgents(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $alive = $this->seedAgent($agents, $members, $organizationId, $actorId, 'Alive', 'alive');
        $deleted = $this->seedAgent($agents, $members, $organizationId, $actorId, 'Gone', 'gone');
        $deleted->softDelete($this->now());

        $agents->save($deleted);
        $sut = new ListAgentsQueryHandler($agents, $members);

        $result = $sut(new ListAgentsQuery($actorId));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->id()->equals($alive->id()));
    }

    public function testShouldIgnoreAgentsWhereUserIsNotMember(): void
    {
        $actorId = new UserId(Uuid::v7());
        $foreignOwnerId = new UserId(Uuid::v7());
        $foreignOrg = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $this->seedAgent($agents, $members, $foreignOrg, $foreignOwnerId, 'Foreign', 'foreign');
        $sut = new ListAgentsQueryHandler($agents, $members);

        self::assertSame([], $sut(new ListAgentsQuery($actorId)));
    }

    private function seedAgent(
        InMemoryAgentRepository $agents,
        InMemoryAgentMemberRepository $members,
        OrganizationId $organizationId,
        UserId $ownerId,
        string $name,
        string $slug,
    ): Agent {
        $agent = Agent::create(
            AgentId::generate(),
            $organizationId,
            $ownerId,
            $slug,
            $name,
            'Role',
            'Instructions.',
            $this->now(),
        );
        $agents->save($agent);
        $members->save(AgentMember::grant($agent->id(), $ownerId, $ownerId, $this->now()));

        return $agent;
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-13T15:00:00+00:00');
    }
}
