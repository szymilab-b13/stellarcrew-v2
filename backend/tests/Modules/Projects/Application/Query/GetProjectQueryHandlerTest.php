<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Application\Query;

use App\Tests\Fixtures\Projects\FakeWorkspacesApi;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\Query\GetProjectQuery;
use Modules\Projects\Application\Query\GetProjectQueryHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class GetProjectQueryHandlerTest extends TestCase
{
    public function testShouldReturnProjectWhenActorHasViewPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, GetProjectQueryHandler::PERMISSION);
        $sut = new GetProjectQueryHandler($repo, $workspaces);

        $result = $sut(new GetProjectQuery($actorId, $project->id()));

        self::assertSame($project, $result);
    }

    public function testShouldThrowNotFoundWhenProjectDoesNotExist(): void
    {
        $sut = new GetProjectQueryHandler(
            new InMemoryProjectRepository(),
            new FakeWorkspacesApi(),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetProjectQuery(new UserId(Uuid::v7()), ProjectId::generate()));
    }

    public function testShouldThrowNotFoundWhenProjectIsSoftDeleted(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $project->softDelete(new DateTimeImmutable('2026-11-09T10:05:00+00:00'));

        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, GetProjectQueryHandler::PERMISSION);
        $sut = new GetProjectQueryHandler($repo, $workspaces);

        $this->expectException(NotFoundHttpException::class);

        $sut(new GetProjectQuery($actorId, $project->id()));
    }

    public function testShouldThrowAccessDeniedWhenActorHasNoViewPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $project = $this->seedProject($workspaceId, 'alpha');
        $repo = new InMemoryProjectRepository();
        $repo->save($project);

        $workspaces = (new FakeWorkspacesApi())->withWorkspace($workspaceId);
        $sut = new GetProjectQueryHandler($repo, $workspaces);

        $this->expectException(AccessDeniedException::class);

        $sut(new GetProjectQuery($actorId, $project->id()));
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
            ->withPermission($workspaceB, $actorId, GetProjectQueryHandler::PERMISSION);
        $sut = new GetProjectQueryHandler($repo, $workspaces);

        $this->expectException(AccessDeniedException::class);

        $sut(new GetProjectQuery($actorId, $project->id()));
    }

    private function seedProject(WorkspaceId $workspaceId, string $slug): Project
    {
        return Project::create(
            ProjectId::generate(),
            $workspaceId,
            ucfirst($slug),
            $slug,
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }
}
