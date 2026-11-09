<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Application\CreateWorkspaceCommand;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\UpdateWorkspaceCommand;
use Modules\Workspaces\Application\UpdateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\WorkspaceNotFoundException;
use Modules\Workspaces\Domain\WorkspacePermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UpdateWorkspaceCommandHandlerTest extends TestCase
{
    public function testShouldRenameAndRepaintWorkspaceWhenOwnerUpdates(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $create = new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock);
        $workspace = $create(new CreateWorkspaceCommand($ownerId, 'Acme'));
        $audit = new SpyAuditLogger();
        $sut = new UpdateWorkspaceCommandHandler($repo, $audit, $clock);

        $updated = $sut(new UpdateWorkspaceCommand($workspace->id(), $ownerId, 'Acme HQ', '#00FF00', 'AH'));

        self::assertSame('Acme HQ', $updated->name());
        self::assertSame('#00FF00', $updated->iconColor());
        self::assertSame('AH', $updated->iconInitials());
    }

    public function testShouldThrowWorkspaceNotFoundWhenWorkspaceMissing(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $sut = new UpdateWorkspaceCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00')),
        );

        $this->expectException(WorkspaceNotFoundException::class);
        $sut(new UpdateWorkspaceCommand(new WorkspaceId(Uuid::v7()), new UserId(Uuid::v7()), 'X'));
    }

    public function testShouldThrowPermissionDeniedWhenActorIsNotOwner(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(new CreateWorkspaceCommand($ownerId, 'Acme'));
        $sut = new UpdateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock);

        $this->expectException(WorkspacePermissionDeniedException::class);
        $sut(new UpdateWorkspaceCommand($workspace->id(), new UserId(Uuid::v7()), 'Hijack'));
    }

    public function testShouldRecordAuditEntryWithBeforeAndAfterStateSnapshots(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(
            new CreateWorkspaceCommand($ownerId, 'Acme', '#111111', 'AA'),
        );
        $audit = new SpyAuditLogger();
        $sut = new UpdateWorkspaceCommandHandler($repo, $audit, $clock);

        $sut(new UpdateWorkspaceCommand($workspace->id(), $ownerId, 'Acme HQ', '#222222', 'HQ'));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(UpdateWorkspaceCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(UpdateWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $workspace->id(), $entry->resourceId);
        self::assertTrue($entry->userId?->equals($ownerId) ?? false);
        self::assertSame('Acme', $entry->metadata['before_state']['name']);
        self::assertSame('#111111', $entry->metadata['before_state']['icon_color']);
        self::assertSame('AA', $entry->metadata['before_state']['icon_initials']);
        self::assertSame('Acme HQ', $entry->metadata['after_state']['name']);
        self::assertSame('#222222', $entry->metadata['after_state']['icon_color']);
        self::assertSame('HQ', $entry->metadata['after_state']['icon_initials']);
    }

    public function testShouldNotRecordAuditWhenWorkspaceIsDeleted(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(new CreateWorkspaceCommand($ownerId, 'Acme'));
        $workspace->softDelete($clock->now());

        $repo->saveWorkspace($workspace);
        $audit = new SpyAuditLogger();
        $sut = new UpdateWorkspaceCommandHandler($repo, $audit, $clock);

        $this->expectException(WorkspaceNotFoundException::class);
        try {
            $sut(new UpdateWorkspaceCommand($workspace->id(), $ownerId, 'New name'));
        } finally {
            self::assertSame([], $audit->entries);
        }
    }
}
