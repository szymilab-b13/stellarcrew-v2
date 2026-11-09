<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Application;

use App\Tests\Fixtures\Projects\FakeWorkspacesApi;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\UpdateProjectCommand;
use Modules\Projects\Application\UpdateProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class UpdateProjectCommandHandlerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-09T10:00:00+00:00';

    private const string UPDATED_AT = '2026-11-09T11:00:00+00:00';

    public function testShouldRenameProjectWhenOnlyNameProvided(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), name: 'Renamed'));

        self::assertSame('Renamed', $result->name());
        self::assertSame('alpha', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
    }

    public function testShouldChangeSlugWhenDifferentFromCurrent(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), slug: 'beta'));

        self::assertSame('beta', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
    }

    public function testShouldLeaveSlugUnchangedWhenSameSlugProvided(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), slug: 'alpha'));

        self::assertSame('alpha', $result->slug());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->updatedAt());
    }

    public function testShouldAppendRandomSuffixWhenNewSlugTakenByAnotherProject(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $other = $this->seedProject($workspaceId, 'beta');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);
        $repo->save($other);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), slug: 'beta'));

        self::assertNotSame('beta', $result->slug());
        self::assertNotSame('alpha', $result->slug());
        self::assertStringStartsWith('beta-', $result->slug());
    }

    public function testShouldChangeDescription(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), description: 'New scope'));

        self::assertSame('New scope', $result->description());
        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
    }

    public function testShouldUpdateAllThreeFieldsAtOnce(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand(
            actorId: $actorId,
            projectId: $project->id(),
            name: 'Renamed',
            slug: 'renamed-slug',
            description: 'Full update',
        ));

        self::assertSame('Renamed', $result->name());
        self::assertSame('renamed-slug', $result->slug());
        self::assertSame('Full update', $result->description());
    }

    public function testShouldLeaveProjectUnchangedWhenAllFieldsNull(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id()));

        self::assertSame('Alpha', $result->name());
        self::assertSame('alpha', $result->slug());
        self::assertNull($result->description());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->updatedAt());
    }

    public function testShouldThrowNotFoundWhenProjectMissing(): void
    {
        $sut = new UpdateProjectCommandHandler(
            new InMemoryProjectRepository(),
            new FakeWorkspacesApi(),
            new FrozenClock(new DateTimeImmutable(self::UPDATED_AT)),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateProjectCommand(new UserId(Uuid::v7()), ProjectId::generate(), name: 'X'));
    }

    public function testShouldThrowNotFoundWhenProjectSoftDeleted(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $project->softDelete(new DateTimeImmutable(self::UPDATED_AT));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $this->expectException(NotFoundHttpException::class);

        $sut(new UpdateProjectCommand($actorId, $project->id(), name: 'X'));
    }

    public function testShouldThrowAccessDeniedWhenActorHasNoEditPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())->withWorkspace($workspaceId);
        $sut = new UpdateProjectCommandHandler(
            $repo,
            $workspaces,
            new FrozenClock(new DateTimeImmutable(self::UPDATED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new UpdateProjectCommand($actorId, $project->id(), name: 'X'));
    }

    public function testShouldCheckPermissionAgainstProjectsOwnWorkspace(): void
    {
        $workspaceA = WorkspaceId::generate();
        $workspaceB = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceA, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceA)
            ->withWorkspace($workspaceB)
            ->withPermission($workspaceB, $actorId, UpdateProjectCommandHandler::PERMISSION);
        $sut = new UpdateProjectCommandHandler(
            $repo,
            $workspaces,
            new FrozenClock(new DateTimeImmutable(self::UPDATED_AT)),
        );

        $this->expectException(AccessDeniedException::class);

        $sut(new UpdateProjectCommand($actorId, $project->id(), name: 'X'));
    }

    public function testShouldStampUpdatedAtFromClock(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $sut = $this->handler($repo, $workspaceId, $actorId);

        $result = $sut(new UpdateProjectCommand($actorId, $project->id(), name: 'Renamed'));

        self::assertEquals(new DateTimeImmutable(self::UPDATED_AT), $result->updatedAt());
        self::assertEquals(new DateTimeImmutable(self::CREATED_AT), $result->createdAt());
    }

    private function handler(
        InMemoryProjectRepository $repo,
        WorkspaceId $workspaceId,
        UserId $actorId,
    ): UpdateProjectCommandHandler {
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, UpdateProjectCommandHandler::PERMISSION);

        return new UpdateProjectCommandHandler(
            $repo,
            $workspaces,
            new FrozenClock(new DateTimeImmutable(self::UPDATED_AT)),
        );
    }

    private function seedProject(WorkspaceId $workspaceId, string $slug): Project
    {
        return Project::create(
            ProjectId::generate(),
            $workspaceId,
            ucfirst($slug),
            $slug,
            null,
            new DateTimeImmutable(self::CREATED_AT),
        );
    }
}
