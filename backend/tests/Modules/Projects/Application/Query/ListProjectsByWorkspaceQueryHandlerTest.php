<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Application\Query;

use App\Tests\Fixtures\Projects\FakeWorkspacesApi;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\Query\ListProjectsByWorkspaceQuery;
use Modules\Projects\Application\Query\ListProjectsByWorkspaceQueryHandler;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class ListProjectsByWorkspaceQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenWorkspaceHasNoProjects(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, ListProjectsByWorkspaceQueryHandler::PERMISSION);
        $sut = new ListProjectsByWorkspaceQueryHandler(new InMemoryProjectRepository(), $workspaces);

        $result = $sut(new ListProjectsByWorkspaceQuery($actorId, $workspaceId));

        self::assertSame([], $result);
    }

    public function testShouldReturnProjectsOfTheGivenWorkspace(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $repo = new InMemoryProjectRepository();
        $repo->save($this->seedProject($workspaceId, 'alpha'));
        $repo->save($this->seedProject($workspaceId, 'beta'));

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, ListProjectsByWorkspaceQueryHandler::PERMISSION);
        $sut = new ListProjectsByWorkspaceQueryHandler($repo, $workspaces);

        $result = $sut(new ListProjectsByWorkspaceQuery($actorId, $workspaceId));

        self::assertCount(2, $result);
        self::assertSame(['alpha', 'beta'], array_map(static fn (Project $p): string => $p->slug(), $result));
    }

    public function testShouldNotLeakProjectsFromOtherWorkspaces(): void
    {
        $workspaceA = WorkspaceId::generate();
        $workspaceB = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $repo = new InMemoryProjectRepository();
        $repo->save($this->seedProject($workspaceA, 'alpha'));
        $repo->save($this->seedProject($workspaceB, 'beta'));

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceA)
            ->withPermission($workspaceA, $actorId, ListProjectsByWorkspaceQueryHandler::PERMISSION);
        $sut = new ListProjectsByWorkspaceQueryHandler($repo, $workspaces);

        $result = $sut(new ListProjectsByWorkspaceQuery($actorId, $workspaceA));

        self::assertCount(1, $result);
        self::assertSame('alpha', $result[0]->slug());
    }

    public function testShouldOmitDeletedProjects(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $repo = new InMemoryProjectRepository();
        $alive = $this->seedProject($workspaceId, 'alive');
        $removed = $this->seedProject($workspaceId, 'removed');
        $removed->softDelete(new DateTimeImmutable('2026-11-09T10:05:00+00:00'));

        $repo->save($alive);
        $repo->save($removed);

        $workspaces = (new FakeWorkspacesApi())
            ->withWorkspace($workspaceId)
            ->withPermission($workspaceId, $actorId, ListProjectsByWorkspaceQueryHandler::PERMISSION);
        $sut = new ListProjectsByWorkspaceQueryHandler($repo, $workspaces);

        $result = $sut(new ListProjectsByWorkspaceQuery($actorId, $workspaceId));

        self::assertCount(1, $result);
        self::assertSame('alive', $result[0]->slug());
    }

    public function testShouldThrowNotFoundWhenWorkspaceDoesNotExist(): void
    {
        $sut = new ListProjectsByWorkspaceQueryHandler(
            new InMemoryProjectRepository(),
            new FakeWorkspacesApi(),
        );

        $this->expectException(NotFoundHttpException::class);

        $sut(new ListProjectsByWorkspaceQuery(new UserId(Uuid::v7()), WorkspaceId::generate()));
    }

    public function testShouldThrowAccessDeniedWhenActorHasNoProjectsViewPermission(): void
    {
        $workspaceId = WorkspaceId::generate();
        $actorId = new UserId(Uuid::v7());
        $workspaces = (new FakeWorkspacesApi())->withWorkspace($workspaceId);
        $sut = new ListProjectsByWorkspaceQueryHandler(new InMemoryProjectRepository(), $workspaces);

        $this->expectException(AccessDeniedException::class);

        $sut(new ListProjectsByWorkspaceQuery($actorId, $workspaceId));
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
