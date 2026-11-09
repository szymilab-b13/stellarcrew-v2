<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Application;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Projects\FakeWorkspacesApi;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\RestoreProjectCommand;
use Modules\Projects\Application\RestoreProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class RestoreProjectCommandHandlerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-09T10:00:00+00:00';

    private const string DELETED_AT = '2026-11-09T11:00:00+00:00';

    private const string RESTORED_AT = '2026-11-09T12:00:00+00:00';

    public function testShouldRestoreSoftDeletedProjectWhenActorHasPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new RestoreProjectCommand($actorId, $project->id()));

        self::assertFalse($result->isDeleted());
        self::assertNull($result->deletedAt());
        self::assertEquals(new DateTimeImmutable(self::RESTORED_AT), $result->updatedAt());
    }

    public function testShouldReturnProjectUnchangedWhenAlreadyActive(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new RestoreProjectCommand($actorId, $project->id()));

        self::assertFalse($result->isDeleted());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->updatedAt());
    }

    public function testShouldThrowNotFoundWhenProjectMissing(): void
    {
        $sut = new RestoreProjectCommandHandler(
            new InMemoryProjectRepository(),
            new FakeWorkspacesApi(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new RestoreProjectCommand(new UserId(Uuid::v7()), ProjectId::generate()));
    }

    public function testShouldThrowAccessDeniedWhenActorHasNoDeletePermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())->withWorkspace($workspaceId);
        $sut = new RestoreProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new RestoreProjectCommand($actorId, $project->id()));
    }

    public function testShouldCheckPermissionAgainstProjectsOwnWorkspace(): void
    {
        $workspaceA = WorkspaceId::generate();
        $workspaceB = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceA);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceA)
            ->withWorkspace($workspaceB)
            ->withPermission($workspaceB, $actorId, RestoreProjectCommandHandler::PERMISSION);
        $sut = new RestoreProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new RestoreProjectCommand($actorId, $project->id()));
    }

    public function testShouldStampUpdatedAtFromClockOnRestore(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new RestoreProjectCommand($actorId, $project->id()));

        self::assertEquals(new DateTimeImmutable(self::RESTORED_AT), $result->updatedAt());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->createdAt());
    }

    public function testShouldRecordAuditEntryWhenRestoringSoftDeletedProject(): void
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
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $audit = new SpyAuditLogger();
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, RestoreProjectCommandHandler::PERMISSION);
        $sut = new RestoreProjectCommandHandler(
            $repo,
            $workspaces,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
        );

        $sut(new RestoreProjectCommand($actorId, $project->id()));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame(RestoreProjectCommandHandler::AUDIT_ACTION, $entry->action);
        self::assertSame(RestoreProjectCommandHandler::AUDIT_RESOURCE_TYPE, $entry->resourceType);
        self::assertSame((string) $project->id(), $entry->resourceId);
        self::assertNotNull($entry->userId);
        self::assertTrue($entry->userId->equals($actorId));
        self::assertSame('Alpha', $entry->metadata['after_state']['name']);
        self::assertSame('alpha', $entry->metadata['after_state']['slug']);
        self::assertSame('Scope', $entry->metadata['after_state']['description']);
    }

    public function testShouldNotRecordAuditEntryWhenProjectAlreadyActive(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId);
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $audit = new SpyAuditLogger();
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, RestoreProjectCommandHandler::PERMISSION);
        $sut = new RestoreProjectCommandHandler(
            $repo,
            $workspaces,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
        );

        $sut(new RestoreProjectCommand($actorId, $project->id()));

        self::assertCount(0, $audit->entries);
    }

    private function handler(
        InMemoryProjectRepository $repo,
        WorkspaceId $workspaceId,
        UserId $actorId,
    ): RestoreProjectCommandHandler {
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, RestoreProjectCommandHandler::PERMISSION);

        return new RestoreProjectCommandHandler(
            $repo,
            $workspaces,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::RESTORED_AT)),
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
