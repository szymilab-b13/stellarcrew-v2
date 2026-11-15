<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use DateTimeImmutable;
use Modules\Agents\Application\CreateAgentCommandHandler;
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

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Renamed PM',
        ));

        self::assertSame('Renamed PM', $updated->name());
        self::assertSame('Project Manager', $updated->role());
        self::assertSame('You are Maja, the PM.', $updated->roleDescription());
    }

    public function testShouldChangeRoleWhenRoleProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            role: 'Senior Architect',
        ));

        self::assertSame('Senior Architect', $updated->role());
        self::assertSame('Maja PM', $updated->name());
    }

    public function testShouldUpdateRoleDescriptionWhenProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            roleDescription: 'You are the new Maja.',
        ));

        self::assertSame('You are the new Maja.', $updated->roleDescription());
    }

    public function testShouldUpdateExpertiseWhenProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            expertise: '- Fresh expertise.',
        ));

        self::assertSame('- Fresh expertise.', $updated->expertise());
    }

    public function testShouldUpdateWorkflowWhenProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            workflow: '- Fresh workflow.',
        ));

        self::assertSame('- Fresh workflow.', $updated->workflow());
    }

    public function testShouldUpdateStyleWhenProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            style: '- Fresh style.',
        ));

        self::assertSame('- Fresh style.', $updated->style());
    }

    public function testShouldUpdateOutputFormatWhenProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            outputFormat: '- Fresh output.',
        ));

        self::assertSame('- Fresh output.', $updated->outputFormat());
    }

    public function testShouldUpdateAllFieldsWhenAllProvided(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Viktor Architect',
            role: 'Architect',
            roleDescription: 'You are Viktor, the architect.',
            expertise: '- Architecture.',
            workflow: '- Diagrams first.',
            style: '- Precise.',
            outputFormat: '- One ADR per decision.',
        ));

        self::assertSame('Viktor Architect', $updated->name());
        self::assertSame('Architect', $updated->role());
        self::assertSame('You are Viktor, the architect.', $updated->roleDescription());
        self::assertSame('- Architecture.', $updated->expertise());
        self::assertSame('- Diagrams first.', $updated->workflow());
        self::assertSame('- Precise.', $updated->style());
        self::assertSame('- One ADR per decision.', $updated->outputFormat());
    }

    public function testShouldLeaveFieldsUntouchedWhenAllNull(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents);

        $updated = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
        ));

        self::assertSame('Maja PM', $updated->name());
        self::assertSame('Project Manager', $updated->role());
        self::assertSame('You are Maja, the PM.', $updated->roleDescription());
        self::assertSame('- Coordination.', $updated->expertise());
        self::assertSame('- Open with briefing.', $updated->workflow());
        self::assertSame('- Terse and direct.', $updated->style());
        self::assertSame('- One-line confirmations.', $updated->outputFormat());
    }

    public function testShouldThrowNotFoundWhenAgentMissing(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $sut = $this->buildHandler(new InMemoryAgentRepository());

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

        $sut = $this->buildHandler($agents);

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

        $sut = $this->buildHandler($agents);

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
        $agent = $this->seedAgent($ownerId, $createdAt);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $sut = $this->buildHandler($agents, $updatedAt);

        $result = $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Renamed',
        ));

        self::assertEquals($createdAt, $result->createdAt());
        self::assertEquals($updatedAt, $result->updatedAt());
    }

    public function testShouldRecordAuditEntryWithBeforeAndAfterStateSnapshots(): void
    {
        $ownerId = new UserId(Uuid::v7());
        $agent = $this->seedAgent($ownerId);
        $agents = new InMemoryAgentRepository();
        $agents->save($agent);

        $audit = new SpyAuditLogger();
        $sut = $this->buildHandler($agents, audit: $audit);

        $sut(new UpdateAgentCommand(
            actorId: $ownerId,
            agentId: $agent->id(),
            name: 'Viktor Architect',
            role: 'Architect',
            roleDescription: 'You are Viktor, the architect.',
            expertise: '- Architecture.',
            workflow: '- Diagrams first.',
            style: '- Precise.',
            outputFormat: '- One ADR per decision.',
        ));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(UpdateAgentCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(CreateAgentCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $agent->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($ownerId));
        self::assertSame('Maja PM', $entry->metadata['before_state']['name']);
        self::assertSame('Project Manager', $entry->metadata['before_state']['role']);
        self::assertSame('You are Maja, the PM.', $entry->metadata['before_state']['role_description']);
        self::assertSame('- Coordination.', $entry->metadata['before_state']['expertise']);
        self::assertSame('- Open with briefing.', $entry->metadata['before_state']['workflow']);
        self::assertSame('- Terse and direct.', $entry->metadata['before_state']['style']);
        self::assertSame('- One-line confirmations.', $entry->metadata['before_state']['output_format']);
        self::assertSame('Viktor Architect', $entry->metadata['after_state']['name']);
        self::assertSame('Architect', $entry->metadata['after_state']['role']);
        self::assertSame('You are Viktor, the architect.', $entry->metadata['after_state']['role_description']);
        self::assertSame('- Architecture.', $entry->metadata['after_state']['expertise']);
        self::assertSame('- Diagrams first.', $entry->metadata['after_state']['workflow']);
        self::assertSame('- Precise.', $entry->metadata['after_state']['style']);
        self::assertSame('- One ADR per decision.', $entry->metadata['after_state']['output_format']);
    }

    private function seedAgent(UserId $ownerId, ?DateTimeImmutable $createdAt = null): Agent
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
            $createdAt ?? new DateTimeImmutable('2026-11-13T15:00:00+00:00'),
        );
    }

    private function buildHandler(
        InMemoryAgentRepository $agents,
        ?DateTimeImmutable $now = null,
        ?SpyAuditLogger $audit = null,
    ): UpdateAgentCommandHandler {
        return new UpdateAgentCommandHandler(
            $agents,
            $audit ?? new SpyAuditLogger(),
            new FrozenClock($now ?? new DateTimeImmutable('2026-11-13T18:30:00+00:00')),
        );
    }
}
