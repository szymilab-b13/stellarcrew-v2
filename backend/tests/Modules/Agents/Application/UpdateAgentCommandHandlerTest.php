<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Application\UpdateAgentCommand;
use Modules\Agents\Application\UpdateAgentCommandHandler;
use Modules\Agents\Domain\Agent;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class UpdateAgentCommandHandlerTest extends TestCase
{
    public function testShouldRenameAgentWhenNameProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Renamed PM',
        ));

        self::assertSame('Renamed PM', $updated->name());
        self::assertSame('Project Manager', $updated->role());
        self::assertSame('You are Maja, the PM.', $updated->instructions());
    }

    public function testShouldChangeRoleWhenRoleProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            role: 'Senior Architect',
        ));

        self::assertSame('Senior Architect', $updated->role());
        self::assertSame('Maja PM', $updated->name());
    }

    public function testShouldUpdateInstructionsWhenInstructionsProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            instructions: 'You are the new Maja with fresh prompt.',
        ));

        self::assertSame('You are the new Maja with fresh prompt.', $updated->instructions());
    }

    public function testShouldUpdateAllFieldsWhenAllProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Viktor Architect',
            role: 'Architect',
            instructions: 'You are Viktor, the architect.',
        ));

        self::assertSame('Viktor Architect', $updated->name());
        self::assertSame('Architect', $updated->role());
        self::assertSame('You are Viktor, the architect.', $updated->instructions());
    }

    public function testShouldLeaveFieldsUntouchedWhenAllNull(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        self::assertSame('Maja PM', $updated->name());
        self::assertSame('Project Manager', $updated->role());
        self::assertSame('You are Maja, the PM.', $updated->instructions());
    }

    public function testShouldThrowNotFoundWhenAgentMissing(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $sut = new UpdateAgentCommandHandler(
            new InMemoryAgentRepository(),
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: new AgentId(Uuid::v7()),
            name: 'anything',
        ));
    }

    public function testShouldThrowNotFoundWhenAgentSoftDeleted(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agent->softDelete(new DateTimeImmutable('2026-11-13T18:00:00+00:00'));

        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'anything',
        ));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotOwner(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $foreignId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new UpdateAgentCommand(
            actorId: $foreignId,
            agentId: $agent->id(),
            name: 'anything',
        ));
    }

    public function testShouldStampUpdatedAtFromClockAndPreserveCreatedAt(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-13T15:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-11-13T18:30:00+00:00');
        $agent = Agent::create(
            new AgentId(Uuid::v7()),
            new OrganizationId(Uuid::v7()),
            $ownerId,
            'maja-pm',
            'Maja PM',
            'Project Manager',
            'You are Maja, the PM.',
            $createdAt,
        );
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new UpdateAgentCommandHandler(
            $agents,
            new FrozenClock($updatedAt),
        );

        $result = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Renamed',
        ));

        self::assertEquals($createdAt, $result->createdAt());
        self::assertEquals($updatedAt, $result->updatedAt());
    }

    private function seedAgent(UserId $ownerId): Agent
    {
        return Agent::create(
            new AgentId(Uuid::v7()),
            new OrganizationId(Uuid::v7()),
            $ownerId,
            'maja-pm',
            'Maja PM',
            'Project Manager',
            'You are Maja, the PM.',
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        );
    }
}
