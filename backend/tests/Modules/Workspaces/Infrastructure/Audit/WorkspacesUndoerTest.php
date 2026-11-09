<?php

declare(strict_types=1);

namespace App\Tests\Modules\Workspaces\Infrastructure\Audit;

use App\Tests\Fixtures\Workspaces\InMemoryWorkspaceRepository;
use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Application\CreateWorkspaceCommandHandler;
use Modules\Workspaces\Application\DeleteWorkspaceCommandHandler;
use Modules\Workspaces\Application\UpdateWorkspaceCommandHandler;
use Modules\Workspaces\Domain\Workspace;
use Modules\Workspaces\Infrastructure\Audit\WorkspacesUndoer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class WorkspacesUndoerTest extends TestCase
{
    public function testShouldSupportAllThreeWorkspaceLifecycleActions(): void
    {
        $sut = $this->makeSut();

        self::assertTrue($sut->supports(CreateWorkspaceCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(UpdateWorkspaceCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeleteWorkspaceCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('other.action'));
    }

    public function testShouldSoftDeleteWorkspaceWhenUndoingCreated(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(CreateWorkspaceCommandHandler::AUDIT_ACTION, $workspace),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($workspace->id());
        self::assertInstanceOf(Workspace::class, $after);
        self::assertTrue($after->isDeleted());
    }

    public function testShouldThrowAlreadyDeletedWhenUndoingCreatedOfDeletedWorkspace(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo);
        $workspace->softDelete(new DateTimeImmutable('2026-11-07T12:00:00+00:00'));

        $repo->saveWorkspace($workspace);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(CreateWorkspaceCommandHandler::AUDIT_ACTION, $workspace),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldRestoreBeforeStateWhenUndoingUpdated(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo, name: 'Acme HQ', iconColor: '#222222', iconInitials: 'HQ');
        $sut = $this->makeSut($repo);

        $entry = new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: UpdateWorkspaceCommandHandler::AUDIT_ACTION,
            resourceType: UpdateWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $workspace->id(),
            beforeState: [
                'name' => 'Acme',
                'icon_color' => '#111111',
                'icon_initials' => 'AC',
            ],
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );

        $sut->undo($entry, new UserId(Uuid::v7()));

        $after = $repo->findById($workspace->id());
        self::assertInstanceOf(Workspace::class, $after);
        self::assertSame('Acme', $after->name());
        self::assertSame('#111111', $after->iconColor());
        self::assertSame('AC', $after->iconInitials());
    }

    public function testShouldThrowMissingStateWhenUndoingUpdatedWithoutBeforeState(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo);
        $sut = $this->makeSut($repo);

        $entry = new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: UpdateWorkspaceCommandHandler::AUDIT_ACTION,
            resourceType: UpdateWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $workspace->id(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldRestoreWorkspaceWhenUndoingDeleted(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo);
        $workspace->softDelete(new DateTimeImmutable('2026-11-07T12:00:00+00:00'));

        $repo->saveWorkspace($workspace);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(DeleteWorkspaceCommandHandler::AUDIT_ACTION, $workspace),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($workspace->id());
        self::assertInstanceOf(Workspace::class, $after);
        self::assertFalse($after->isDeleted());
    }

    public function testShouldThrowNotDeletedWhenUndoingDeletedOfActiveWorkspace(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $workspace = $this->seedActiveWorkspace($repo);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(DeleteWorkspaceCommandHandler::AUDIT_ACTION, $workspace),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldThrowWorkspaceNotFoundWhenEntryResourceMissing(): void
    {
        $repo = new InMemoryWorkspaceRepository();
        $sut = $this->makeSut($repo);

        $entry = new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: CreateWorkspaceCommandHandler::AUDIT_ACTION,
            resourceType: CreateWorkspaceCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) new WorkspaceId(Uuid::v7()),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    private function makeSut(?InMemoryWorkspaceRepository $repo = null, ?Clock $clock = null): WorkspacesUndoer
    {
        return new WorkspacesUndoer(
            $repo ?? new InMemoryWorkspaceRepository(),
            $clock ?? new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );
    }

    private function seedActiveWorkspace(
        InMemoryWorkspaceRepository $repo,
        string $name = 'Acme',
        string $iconColor = '#111111',
        string $iconInitials = 'AC',
    ): Workspace {
        $workspace = Workspace::create(
            new WorkspaceId(Uuid::v7()),
            $name,
            strtolower(str_replace(' ', '-', $name)),
            $iconColor,
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
            $iconInitials,
        );
        $repo->saveWorkspace($workspace);

        return $workspace;
    }

    private function entryFor(string $action, Workspace $workspace): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: 'workspace',
            resourceId: (string) $workspace->id(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
