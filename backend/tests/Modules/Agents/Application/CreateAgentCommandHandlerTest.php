<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Application;

use App\Tests\Fixtures\Agents\FakeOrganizationsApi;
use App\Tests\Fixtures\Agents\FakePlanLimitsService;
use App\Tests\Fixtures\Agents\InMemoryAgentMemberRepository;
use App\Tests\Fixtures\Agents\InMemoryAgentRepository;
use App\Tests\Fixtures\Core\SpyAuditLogger;
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
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );

        $agent = $sut($this->command(actorId: $actorId, name: 'Maja PM'));

        self::assertSame('maja-pm', $agent->slug());
        self::assertSame('Maja PM', $agent->name());
        self::assertNull($agent->sourceTemplateId());
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

        $agent = $sut($this->command(
            actorId: $actorId,
            name: 'Viktor Architect',
            role: 'Architect',
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
        $sut($this->command(actorId: $actorId, name: 'Alpha'));

        $second = $sut($this->command(actorId: $actorId, name: 'Alpha'));

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

        $agent = $sut($this->command(actorId: $actorId, name: 'Maja'));

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
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );

        $this->expectException(OwnerHasNoOrganizationException::class);

        $sut($this->command(actorId: $actorId, name: 'Maja'));
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
        $sut($this->command(actorId: $actorId, name: 'First'));

        $this->expectException(AgentLimitReachedException::class);

        $sut($this->command(actorId: $actorId, name: 'Second'));
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
        $sut($this->command(actorId: $actorId, name: 'First', slug: 'maja'));

        $this->expectException(AgentSlugAlreadyTakenException::class);

        $sut($this->command(actorId: $actorId, name: 'Second', slug: 'maja'));
    }

    public function testShouldPersistAllFivePromptSections(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
        );

        $agent = $sut(new CreateAgentCommand(
            actorId: $actorId,
            name: 'Maja PM',
            role: 'Project Manager',
            roleDescription: 'You are Maja, the PM.',
            expertise: '- Coordination.',
            workflow: '- Open with briefing.',
            style: '- Terse and direct.',
            outputFormat: '- One-line confirmations.',
        ));

        self::assertSame('You are Maja, the PM.', $agent->roleDescription());
        self::assertSame('- Coordination.', $agent->expertise());
        self::assertSame('- Open with briefing.', $agent->workflow());
        self::assertSame('- Terse and direct.', $agent->style());
        self::assertSame('- One-line confirmations.', $agent->outputFormat());
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

        $agent = $sut($this->command(actorId: $actorId, name: 'Timed'));

        self::assertEquals($now, $agent->createdAt());
        self::assertEquals($now, $agent->updatedAt());
    }

    public function testShouldRecordAuditEntryWithActorAndAfterStateSnapshot(): void
    {
        $actorId = new UserId(Uuid::v7());
        $organizationId = new OrganizationId(Uuid::v7());
        $audit = new SpyAuditLogger();
        $sut = $this->buildHandler(
            actorId: $actorId,
            organizationId: $organizationId,
            audit: $audit,
        );

        $agent = $sut(new CreateAgentCommand(
            actorId: $actorId,
            name: 'Maja PM',
            role: 'Project Manager',
            roleDescription: 'You are Maja, the PM.',
            expertise: '- Coordination.',
            workflow: '- Open with briefing.',
            style: '- Terse and direct.',
            outputFormat: '- One-line confirmations.',
            slug: 'maja',
        ));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(CreateAgentCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(CreateAgentCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $agent->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($actorId));
        self::assertArrayHasKey('after_state', $entry->metadata);
        self::assertSame((string) $agent->id(), $entry->metadata['after_state']['id']);
        self::assertSame((string) $organizationId, $entry->metadata['after_state']['organization_id']);
        self::assertSame((string) $actorId, $entry->metadata['after_state']['owner_user_id']);
        self::assertNull($entry->metadata['after_state']['source_template_id']);
        self::assertSame('maja', $entry->metadata['after_state']['slug']);
        self::assertSame('Maja PM', $entry->metadata['after_state']['name']);
        self::assertSame('Project Manager', $entry->metadata['after_state']['role']);
        self::assertSame('You are Maja, the PM.', $entry->metadata['after_state']['role_description']);
        self::assertSame('- Coordination.', $entry->metadata['after_state']['expertise']);
        self::assertSame('- Open with briefing.', $entry->metadata['after_state']['workflow']);
        self::assertSame('- Terse and direct.', $entry->metadata['after_state']['style']);
        self::assertSame('- One-line confirmations.', $entry->metadata['after_state']['output_format']);
        self::assertTrue($entry->metadata['after_state']['is_active']);
    }

    private function command(
        UserId $actorId,
        string $name = 'Maja PM',
        string $role = 'Project Manager',
        ?string $slug = null,
    ): CreateAgentCommand {
        return new CreateAgentCommand(
            actorId: $actorId,
            name: $name,
            role: $role,
            roleDescription: 'You are the agent.',
            expertise: '- Core expertise.',
            workflow: '- Core workflow.',
            style: '- Core style.',
            outputFormat: '- Core output format.',
            slug: $slug,
        );
    }

    private function buildHandler(
        UserId $actorId,
        OrganizationId $organizationId,
        ?InMemoryAgentMemberRepository $members = null,
        int $agentLimit = 5,
        ?DateTimeImmutable $now = null,
        ?SpyAuditLogger $audit = null,
    ): CreateAgentCommandHandler {
        $organizations = (new FakeOrganizationsApi())->withOrganization($organizationId, $actorId);
        $planLimits = (new FakePlanLimitsService())->withLimit(LimitType::Agents, $agentLimit);

        return new CreateAgentCommandHandler(
            new InMemoryAgentRepository(),
            $members ?? new InMemoryAgentMemberRepository(),
            $organizations,
            $planLimits,
            $audit ?? new SpyAuditLogger(),
            new FrozenClock($now ?? new DateTimeImmutable('2026-11-13T15:00:00+00:00')),
        );
    }
}
