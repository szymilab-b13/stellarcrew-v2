<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\FakeOrganizationsApi;
use App\Tests\Fixtures\Agents\FakePlanLimitsService;
use App\Tests\Fixtures\Agents\InMemoryAgentMemberRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Application\CreateAgentCommand;
use Modules\Agents\Application\CreateAgentCommandHandler;
use Modules\Agents\Domain\AgentLimitReachedException;
use Modules\Agents\Domain\AgentSlugAlreadyTakenException;
use Modules\Shared\Api\LimitType;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Workspaces\Api\Exception\OwnerHasNoOrganizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateAgentCommandHandlerTest extends TestCase
{
    public function testShouldPersistAgentWithAutoSlugWhenSlugMissing(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $agents = new InMemoryAgentRepository();
        $members = new InMemoryAgentMemberRepository();
        $organizations = (new FakeOrganizationsApi())->withOrganization($organizationId, $actorId);
        $planLimits = (new FakePlanLimitsService())->withLimit(LimitType::Agents, 5);
        $sut = new CreateAgentCommandHandler(
            $agents,
            $members,
            $organizations,
            $planLimits,
            new FrozenClock(new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );

        $agent = $sut(new CreateAgentCommand(
            actorId: $actorId,
            name: 'Maja PM',
            role: 'Project Manager',
            instructions: 'You are Maja, the PM.',
        ));

        self::assertSame('maja-pm', $agent->slug());
        self::assertSame('Maja PM', $agent->name());
        self::assertTrue($agent->organizationId()->equals($organizationId));
        self::assertTrue($agent->ownerUserId()->equals($actorId));
        self::assertNotNull($agents->findById($agent->id()));
    }

    public function testShouldPersistAgentWithProvidedSlug(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
        );

        $agent = $sut(new CreateAgentCommand(
            actorId: $actorId,
            name: 'Viktor Architect',
            role: 'Architect',
            instructions: 'You are Viktor.',
            slug: 'viktor',
        ));

        self::assertSame('viktor', $agent->slug());
    }

    public function testShouldAppendRandomSuffixWhenAutoSlugClashes(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            agentLimit: 10,
        );
        $sut(new CreateAgentCommand($actorId, 'Alpha', 'Role', 'Instructions.'));

        $second = $sut(new CreateAgentCommand($actorId, 'Alpha', 'Role', 'Instructions.'));

        self::assertNotSame('alpha', $second->slug());
        self::assertStringStartsWith('alpha-', $second->slug());
    }

    public function testShouldGrantOwnerAgentMembershipAfterCreate(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $members = new InMemoryAgentMemberRepository();
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            members: $members,
        );

        $agent = $sut(new CreateAgentCommand($actorId, 'Maja', 'PM', 'You are Maja.'));

        $granted = $members->find($agent->id(), $actorId);
        self::assertNotNull($granted);
        self::assertTrue($granted->userId()->equals($actorId));
        self::assertTrue($granted->grantedBy()->equals($actorId));
    }

    public function testShouldThrowWhenActorHasNoOrganization(): void
    {
        $actorId = new UserId(Uuid::v7());
        $sut = new CreateAgentCommandHandler(
            new InMemoryAgentRepository(),
            new InMemoryAgentMemberRepository(),
            new FakeOrganizationsApi(),
            (new FakePlanLimitsService())->withLimit(LimitType::Agents, 5),
            new FrozenClock(new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );

        $this->expectException(OwnerHasNoOrganizationException::class);

        $sut(new CreateAgentCommand($actorId, 'Maja', 'PM', 'You are Maja.'));
    }

    public function testShouldThrowWhenAgentLimitReached(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            agentLimit: 1,
        );
        $sut(new CreateAgentCommand($actorId, 'First', 'PM', 'Instructions.'));

        $this->expectException(AgentLimitReachedException::class);

        $sut(new CreateAgentCommand($actorId, 'Second', 'PM', 'Instructions.'));
    }

    public function testShouldThrowWhenProvidedSlugAlreadyTakenInOrganization(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            agentLimit: 10,
        );
        $sut(new CreateAgentCommand($actorId, 'First', 'PM', 'Instructions.', slug: 'maja'));

        $this->expectException(AgentSlugAlreadyTakenException::class);

        $sut(new CreateAgentCommand($actorId, 'Second', 'PM', 'Instructions.', slug: 'maja'));
    }

    public function testShouldStampCreatedAtAndUpdatedAtFromClock(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-13T15:00:00+00:00');
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            now: $now,
        );

        $agent = $sut(new CreateAgentCommand($actorId, 'Timed', 'PM', 'Instructions.'));

        self::assertEquals($now, $agent->createdAt());
        self::assertEquals($now, $agent->updatedAt());
    }

    private function buildHandler(
        UserId $actorId,
        OrganizationId $organizationId,
        ?InMemoryAgentMemberRepository $members = null,
        int $agentLimit = 5,
        ?DateTimeImmutable $now = null,
    ): CreateAgentCommandHandler {
        $organizations = (new FakeOrganizationsApi())->withOrganization($organizationId, $actorId);
        $planLimits = (new FakePlanLimitsService())->withLimit(LimitType::Agents, $agentLimit);

        return new CreateAgentCommandHandler(
            new InMemoryAgentRepository(),
            $members ?? new InMemoryAgentMemberRepository(),
            $organizations,
            $planLimits,
            new FrozenClock($now ?? new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );
    }
}
