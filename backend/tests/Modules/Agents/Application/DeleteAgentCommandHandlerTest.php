<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use DateTimeImmutable;
use Modules\Agents\Application\CreateAgentCommandHandler;
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

        $sut = $this->buildHandler($agents);

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
        $sut = $this->buildHandler(new InMemoryAgentRepository());

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

        $sut = $this->buildHandler($agents);

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

        $sut = $this->buildHandler($agents);

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

        $audit = new SpyAuditLogger();
        $sut = $this->buildHandler($agents, audit: $audit);

        $sut(new DeleteAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        $persisted = $agents->findById($agent->id());
        self::assertNotNull($persisted);
        self::assertTrue($persisted->isDeleted());
        self::assertEquals($firstDeletedAt, $persisted->deletedAt());
        self::assertCount(0, $audit->entries);
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

        $sut = $this->buildHandler($agents, $deletedAt);

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

    public function testShouldRecordAuditEntryWithBeforeStateSnapshot(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $audit = new SpyAuditLogger();
        $sut = $this->buildHandler($agents, audit: $audit);

        $sut(new DeleteAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(DeleteAgentCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(CreateAgentCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $agent->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($ownerId));
        self::assertSame((string) $agent->id(), $entry->metadata['before_state']['id']);
        self::assertSame('maja-pm', $entry->metadata['before_state']['slug']);
        self::assertSame('Maja PM', $entry->metadata['before_state']['name']);
        self::assertSame('Project Manager', $entry->metadata['before_state']['role']);
        self::assertSame('You are Maja, the PM.', $entry->metadata['before_state']['role_description']);
        self::assertSame('- Coordination.', $entry->metadata['before_state']['expertise']);
        self::assertSame('- Open with briefing.', $entry->metadata['before_state']['workflow']);
        self::assertSame('- Terse and direct.', $entry->metadata['before_state']['style']);
        self::assertSame('- One-line confirmations.', $entry->metadata['before_state']['output_format']);
        self::assertTrue($entry->metadata['before_state']['is_active']);
        self::assertNull($entry->metadata['before_state']['source_template_id']);
        self::assertArrayNotHasKey('after_state', $entry->metadata);
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

    private function buildHandler(
        InMemoryAgentRepository $agents,
        ?DateTimeImmutable $now = null,
        ?SpyAuditLogger $audit = null,
    ): DeleteAgentCommandHandler {
        return new DeleteAgentCommandHandler(
            $agents,
            $audit ?? new SpyAuditLogger(),
            new FrozenClock($now ?? new DateTimeImmutable('2026-11-13T19:00:00+00:00')),
        );
    }
}
