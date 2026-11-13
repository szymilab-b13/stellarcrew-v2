<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application\Query;

use App\Tests\Fixtures\Agents\InMemoryAgentMemberRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Application\Query\GetAgentQuery;
use Modules\Agents\Application\Query\GetAgentQueryHandler;
use Modules\Agents\Domain\Agent;
use Modules\Agents\Domain\AgentMember;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class GetAgentQueryHandlerTest extends TestCase
{
    public function testShouldReturnAgentWhenActorIsOwner(): void
    {
        $actorId = new UserId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $agent = $this->seedAgent($agents, $members, new OrganizationId(Uuid::v7()), $actorId, 'Maja', 'maja');
        $sut = new GetAgentQueryHandler($agents, $members);

        $result = $sut(new GetAgentQuery($actorId, $agent->id()));

        self::assertTrue($result->id()->equals($agent->id()));
    }

    public function testShouldReturnAgentWhenActorIsSharedNonOwnerMember(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $memberId = new UserId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $agent = $this->seedAgent($agents, $members, new OrganizationId(Uuid::v7()), $ownerId, 'Viktor', 'viktor');
        $members->save(AgentMember::grant($agent->id(), $memberId, $ownerId, $this->now()));
        $sut = new GetAgentQueryHandler($agents, $members);

        $result = $sut(new GetAgentQuery($memberId, $agent->id()));

        self::assertTrue($result->id()->equals($agent->id()));
    }

    public function testShouldThrowNotFoundWhenAgentDoesNotExist(): void
    {
        $sut = new GetAgentQueryHandler(new InMemoryAgentRepository(), new InMemoryAgentMemberRepository());

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetAgentQuery(new UserId(Uuid::v7()), AgentId::generate()));
    }

    public function testShouldThrowNotFoundWhenAgentIsSoftDeleted(): void
    {
        $actorId = new UserId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $agent = $this->seedAgent($agents, $members, new OrganizationId(Uuid::v7()), $actorId, 'Gone', 'gone');
        $agent->softDelete($this->now());

        $agents->save($agent);
        $sut = new GetAgentQueryHandler($agents, $members);

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetAgentQuery($actorId, $agent->id()));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotMember(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $foreignId = new UserId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $agent = $this->seedAgent($agents, $members, new OrganizationId(Uuid::v7()), $ownerId, 'Private', 'private');
        $sut = new GetAgentQueryHandler($agents, $members);

        $this->expectException(AccessDeniedException::class);

        $sut(new GetAgentQuery($foreignId, $agent->id()));
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
