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
use Modules\Workspaces\Application\DeleteWorkspaceCommand;
use Modules\Workspaces\Application\DeleteWorkspaceCommandHandler;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Domain\WorkspaceNotFoundException;
use Modules\Workspaces\Domain\WorkspacePermissionDeniedException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeleteWorkspaceCommandHandlerTest extends TestCase
{
    public function testShouldSoftDeleteWorkspaceWhenOwnerDeletes(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(new CreateWorkspaceCommand($ownerId, 'Acme'));
        $sut = new DeleteWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock);

        $sut(new DeleteWorkspaceCommand($workspace->id(), $ownerId));

        $after = $repo->findById($workspace->id());
        self::assertInstanceOf(Workspace::class, $after);
        self::assertTrue($after->isDeleted());
    }

    public function testShouldThrowWorkspaceNotFoundWhenWorkspaceMissing(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $sut = new DeleteWorkspaceCommandHandler(
            $repo,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00')),
        );

        $this->expectException(WorkspaceNotFoundException::class);
        $sut(new DeleteWorkspaceCommand(new WorkspaceId(Uuid::v7()), new UserId(Uuid::v7())));
    }

    public function testShouldThrowPermissionDeniedWhenActorIsNotOwner(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(new CreateWorkspaceCommand($ownerId, 'Acme'));
        $sut = new DeleteWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock);

        $this->expectException(WorkspacePermissionDeniedException::class);
        $sut(new DeleteWorkspaceCommand($workspace->id(), new UserId(Uuid::v7())));
    }

    public function testShouldRecordAuditEntryWithBeforeStateSnapshot(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $clock = new FrozenClock(new DateTimeImmutable('2026-11-06T10:00:00+00:00'));
        $ownerId = new UserId(Uuid::v7());
        $workspace = (new CreateWorkspaceCommandHandler($repo, new SpyAuditLogger(), $clock))(
            new CreateWorkspaceCommand($ownerId, 'Acme Corp', '#FF0000', 'AC'),
        );
        $audit = new SpyAuditLogger();
        $sut = new DeleteWorkspaceCommandHandler($repo, $audit, $clock);

        $sut(new DeleteWorkspaceCommand($workspace->id(), $ownerId));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(DeleteWorkspaceCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(DeleteWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $workspace->id(), $entry->resourceId);
        self::assertTrue($entry->userId?->equals($ownerId) ?? false);
        self::assertSame('Acme Corp', $entry->metadata['before_state']['name']);
        self::assertSame('acme-corp', $entry->metadata['before_state']['slug']);
        self::assertSame('#FF0000', $entry->metadata['before_state']['icon_color']);
        self::assertSame('AC', $entry->metadata['before_state']['icon_initials']);
        self::assertSame((string) $ownerId, $entry->metadata['before_state']['owner_id']);
        self::assertArrayNotHasKey('after_state', $entry->metadata);
    }
}
