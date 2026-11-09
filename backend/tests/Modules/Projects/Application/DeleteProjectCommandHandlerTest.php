<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Projects\FakeWorkspacesApi;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\DeleteProjectCommand;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class DeleteProjectCommandHandlerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-09T10:00:00+00:00';

    private const string DELETED_AT = '2026-11-09T11:00:00+00:00';

    public function testShouldSoftDeleteProjectWhenActorHasPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $sut(new DeleteProjectCommand($actorId, $project->id()));

        $stored = $repo->findById($project->id());
        self::assertNotNull($stored);
        self::assertTrue($stored->isDeleted());
        self::assertEquals(new DateTimeImmutable(self::DELETED_AT), $stored->deletedAt());
        self::assertEquals(new DateTimeImmutable(self::DELETED_AT), $stored->updatedAt());
    }

    public function testShouldThrowNotFoundWhenProjectMissing(): void
    {
        $sut = new DeleteProjectCommandHandler(
            new InMemoryProjectRepository(),
            new FakeWorkspacesApi(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::DELETED_AT)),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new DeleteProjectCommand(new UserId(Uuid::v7()), ProjectId::generate()));
    }

    public function testShouldThrowNotFoundWhenProjectAlreadySoftDeleted(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $this->expectException(NotFoundHttpException::class);

        $sut(new DeleteProjectCommand($actorId, $project->id()));
    }

    public function testShouldThrowAccessDeniedWhenActorHasNoDeletePermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())->withWorkspace($workspaceId);
        $sut = new DeleteProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::DELETED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new DeleteProjectCommand($actorId, $project->id()));
    }

    public function testShouldCheckPermissionAgainstProjectsOwnWorkspace(): void
    {
        $workspaceA = WorkspaceId::generate();
        $workspaceB = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceA);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceA)
            ->withWorkspace($workspaceB)
            ->withPermission($workspaceB, $actorId, DeleteProjectCommandHandler::PERMISSION);
        $sut = new DeleteProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::DELETED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new DeleteProjectCommand($actorId, $project->id()));
    }

    public function testShouldStampDeletedAtFromClock(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $sut(new DeleteProjectCommand($actorId, $project->id()));

        $stored = $repo->findById($project->id());
        self::assertNotNull($stored);
        self::assertEquals(new DateTimeImmutable(self::DELETED_AT), $stored->deletedAt());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $stored->createdAt());
    }

    public function testShouldRecordAuditEntryWithBeforeStateSnapshot(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = Project::create(
            ProjectId::generate(),
            $workspaceId,
            'Alpha',
            'alpha',
            'Scope',
            new DateTimeImmutable(self::CREATED_AT),
        );
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $audit = new SpyAuditLogger();
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, DeleteProjectCommandHandler::PERMISSION);
        $sut = new DeleteProjectCommandHandler(
            $repo,
            $workspaces,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::DELETED_AT)),
        );

        $sut(new DeleteProjectCommand($actorId, $project->id()));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(DeleteProjectCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(DeleteProjectCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $project->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($actorId));
        self::assertSame('Alpha', $entry->metadata['before_state']['name']);
        self::assertSame('alpha', $entry->metadata['before_state']['slug']);
        self::assertSame('Scope', $entry->metadata['before_state']['description']);
        self::assertSame((string) $workspaceId, $entry->metadata['before_state']['workspace_id']);
        self::assertArrayNotHasKey('after_state', $entry->metadata);
    }

    private function handler(
        InMemoryProjectRepository $repo,
        WorkspaceId $workspaceId,
        UserId $actorId,
    ): DeleteProjectCommandHandler {
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, DeleteProjectCommandHandler::PERMISSION);

        return new DeleteProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::DELETED_AT)),
        );
    }

    private function seedProject(WorkspaceId $workspaceId): Project
    {
        return Project::create(
            ProjectId::generate(),
            $workspaceId,
            'Alpha',
            'alpha',
            null,
            new DateTimeImmutable(self::CREATED_AT),
        );
    }
}
