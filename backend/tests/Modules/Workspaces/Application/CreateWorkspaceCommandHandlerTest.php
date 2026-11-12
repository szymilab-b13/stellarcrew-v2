<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\WorkspaceRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CreateWorkspaceCommandHandlerTest extends TestCase
{
    public function testShouldPersistWorkspaceAndOwnerMembershipWhenCommandExecuted(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $ownerId = new UserId(Uuid::v7());
        $sut = new CreateWorkspaceCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00')),
        );

        $organizationId = new OrganizationId(Uuid::v7());
        $workspace = $sut(new CreateWorkspaceCommand($ownerId, $organizationId, 'Acme Corp'));

        self::assertSame(1, $repo->count());
        self::assertSame('Acme Corp', $workspace->name());
        self::assertSame('acme-corp', $workspace->slug());
        self::assertTrue($workspace->ownerId()->equals($ownerId));
        self::assertTrue($workspace->organizationId()->equals($organizationId));
        $membership = $repo->findMembership($workspace->id(), $ownerId);
        self::assertNotNull($membership);
        self::assertSame(WorkspaceRole::Owner, $membership->role());
    }

    public function testShouldAppendSuffixWhenSlugAlreadyTaken(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $sut = new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock);
        $sut(new CreateWorkspaceCommand(new UserId(Uuid::v7()), new OrganizationId(Uuid::v7()), 'Acme'));

        $workspace = $sut(new CreateWorkspaceCommand(new UserId(Uuid::v7()), new OrganizationId(Uuid::v7()), 'Acme'));

        self::assertNotSame('acme', $workspace->slug());
        self::assertStringStartsWith('acme-', $workspace->slug());
    }

    public function testShouldRecordAuditEntryWithOwnerAsActorAndAfterStateSnapshot(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $audit = new SpyAuditLogger();
        $ownerId = new UserId(Uuid::v7());
        $sut = new CreateWorkspaceCommandHandler(
            $repo,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00')),
        );

        $organizationId = new OrganizationId(Uuid::v7());
        $workspace = $sut(new CreateWorkspaceCommand($ownerId, $organizationId, 'Acme Corp', '#FF0000', 'AC'));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(CreateWorkspaceCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(CreateWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $workspace->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($ownerId));
        self::assertArrayHasKey('after_state', $entry->metadata);
        self::assertSame('Acme Corp', $entry->metadata['after_state']['name']);
        self::assertSame('acme-corp', $entry->metadata['after_state']['slug']);
        self::assertSame('#FF0000', $entry->metadata['after_state']['icon_color']);
        self::assertSame('AC', $entry->metadata['after_state']['icon_initials']);
        self::assertSame((string) $ownerId, $entry->metadata['after_state']['owner_id']);
        self::assertSame((string) $organizationId, $entry->metadata['after_state']['organization_id']);
    }
}
