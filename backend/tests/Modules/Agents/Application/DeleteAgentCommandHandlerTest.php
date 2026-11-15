<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use DateTimeImmutable;
use Modules\Agents\Application\DeleteAgentCommand;
use Modules\Agents\Application\DeleteAgentCommandHandler;
use Modules\Agents\Domain\Agent;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class DeleteAgentCommandHandlerTest extends TestCase
{
    public function testShouldSoftDeleteAgentWhenActorIsOwner(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new DeleteAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );

        $sut(new DeleteAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        $persisted = $agents->findById($agent->id());
        self::assertNotNull($persisted);
        self::assertTrue($persisted->isDeleted());
    }

    public function testShouldThrowNotFoundWhenAgentMissing(): void
    {
        $sut = new DeleteAgentCommandHandler(
            new InMemoryAgentRepository(),
            new FrozenClock(new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new DeleteAgentCommand(
            actorId: new UserId(Uuid::v7()),
            agentId: new AgentId(Uuid::v7()),
        ));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotOwner(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $foreignId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new DeleteAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new DeleteAgentCommand(
            actorId: $foreignId,
            agentId: $agent->id(),
        ));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotOwnerEvenForSoftDeletedAgent(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $foreignId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agent->softDelete(new DateTimeImmutable('2026-11-13T18:00:00+00:00'));

        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new DeleteAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new DeleteAgentCommand(
            actorId: $foreignId,
            agentId: $agent->id(),
        ));
    }

    public function testShouldBeIdempotentWhenAgentAlreadySoftDeleted(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $firstDeletedAt = new DateTimeImmutable('2026-11-13T18:00:00+00:00');
        $agent = $this->seedAgent($ownerId);
        $agent->softDelete($firstDeletedAt);

        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new DeleteAgentCommandHandler(
            $agents,
            new FrozenClock(new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );

        $sut(new DeleteAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        $persisted = $agents->findById($agent->id());
        self::assertNotNull($persisted);
        self::assertTrue($persisted->isDeleted());
        self::assertEquals($firstDeletedAt, $persisted->deletedAt());
    }

    public function testShouldStampDeletedAtAndUpdatedAtFromClock(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-13T15:00:00+00:00');
        $deletedAt = new DateTimeImmutable('2026-11-13T19:00:00+00:00');
        $agent = Agent::create(
            new AgentId(Uuid::v7()),
            new OrganizationId(Uuid::v7()),
            $ownerId,
            'maja-pm',
            'Maja PM',
            'Project Manager',
            'You are Maja, the PM.',
            '- Coordination.',
            '- Open with briefing.',
            '- Terse and direct.',
            '- One-line confirmations.',
            $createdAt,
        );
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = new DeleteAgentCommandHandler(
            $agents,
            new FrozenClock($deletedAt),
        );

        $sut(new DeleteAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        $persisted = $agents->findById($agent->id());
        self::assertNotNull($persisted);
        self::assertEquals($deletedAt, $persisted->deletedAt());
        self::assertEquals($deletedAt, $persisted->updatedAt());
        self::assertEquals($createdAt, $persisted->createdAt());
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
            '- Coordination.',
            '- Open with briefing.',
            '- Terse and direct.',
            '- One-line confirmations.',
            new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        );
    }
}
