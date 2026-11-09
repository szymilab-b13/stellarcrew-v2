<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Infrastructure\Audit;

use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Projects\Application\CreateProjectCommandHandler;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Application\RestoreProjectCommandHandler;
use Modules\Projects\Application\UpdateProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Projects\Infrastructure\Audit\ProjectsUndoer;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ProjectsUndoerTest extends TestCase
{
    private const string CREATED_AT = '2026-11-09T10:00:00+00:00';

    private const string DELETED_AT = '2026-11-09T11:00:00+00:00';

    private const string UNDO_AT = '2026-11-09T12:00:00+00:00';

    public function testShouldSupportAllFourProjectLifecycleActions(): void
    {
        $sut = $this->makeSut();

        self::assertTrue($sut->supports(CreateProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(UpdateProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(DeleteProjectCommandHandler::AUDIT_ACTION));
        self::assertTrue($sut->supports(RestoreProjectCommandHandler::AUDIT_ACTION));
        self::assertFalse($sut->supports('other.action'));
    }

    public function testShouldSoftDeleteProjectWhenUndoingCreated(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(CreateProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($project->id());
        self::assertInstanceOf(Project::class, $after);
        self::assertTrue($after->isDeleted());
    }

    public function testShouldThrowAlreadyDeletedWhenUndoingCreatedOfDeletedProject(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($project);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(CreateProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldRestoreBeforeStateWhenUndoingUpdated(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo, name: 'Renamed', slug: 'renamed', description: 'New scope');
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($project, before: [
            'name' => 'Alpha',
            'slug' => 'alpha',
            'description' => 'Original scope',
        ]);

        $sut->undo($entry, new UserId(Uuid::v7()));

        $after = $repo->findById($project->id());
        self::assertInstanceOf(Project::class, $after);
        self::assertSame('Alpha', $after->name());
        self::assertSame('alpha', $after->slug());
        self::assertSame('Original scope', $after->description());
    }

    public function testShouldRestoreNullDescriptionWhenUndoingUpdatedWithNullInBeforeState(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo, description: 'Added description');
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($project, before: [
            'name' => 'Alpha',
            'slug' => 'alpha',
            'description' => null,
        ]);

        $sut->undo($entry, new UserId(Uuid::v7()));

        $after = $repo->findById($project->id());
        self::assertInstanceOf(Project::class, $after);
        self::assertNull($after->description());
    }

    public function testShouldThrowMissingStateWhenUndoingUpdatedWithoutBeforeState(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($project, before: null);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldThrowProjectDeletedWhenUndoingUpdatedOfSoftDeletedProject(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($project);
        $sut = $this->makeSut($repo);

        $entry = $this->updatedEntry($project, before: [
            'name' => 'Alpha',
            'slug' => 'alpha',
            'description' => null,
        ]);

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    public function testShouldRestoreProjectWhenUndoingDeleted(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($project);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(DeleteProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($project->id());
        self::assertInstanceOf(Project::class, $after);
        self::assertFalse($after->isDeleted());
    }

    public function testShouldThrowNotDeletedWhenUndoingDeletedOfActiveProject(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(DeleteProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldSoftDeleteProjectWhenUndoingRestored(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $sut = $this->makeSut($repo);

        $sut->undo(
            $this->entryFor(RestoreProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );

        $after = $repo->findById($project->id());
        self::assertInstanceOf(Project::class, $after);
        self::assertTrue($after->isDeleted());
    }

    public function testShouldThrowAlreadyDeletedWhenUndoingRestoredOfSoftDeletedProject(): void
    {
        $repo = new InMemoryProjectRepository();
        $project = $this->seedActiveProject($repo);
        $project->softDelete(new DateTimeImmutable(self::DELETED_AT));

        $repo->save($project);
        $sut = $this->makeSut($repo);

        $this->expectException(UndoConflictException::class);
        $sut->undo(
            $this->entryFor(RestoreProjectCommandHandler::AUDIT_ACTION, $project),
            new UserId(Uuid::v7()),
        );
    }

    public function testShouldThrowProjectNotFoundWhenEntryResourceMissing(): void
    {
        $repo = new InMemoryProjectRepository();
        $sut = $this->makeSut($repo);

        $entry = new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: CreateProjectCommandHandler::AUDIT_ACTION,
            resourceType: CreateProjectCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) ProjectId::generate(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );

        $this->expectException(UndoConflictException::class);
        $sut->undo($entry, new UserId(Uuid::v7()));
    }

    private function makeSut(?InMemoryProjectRepository $repo = null, ?Clock $clock = null): ProjectsUndoer
    {
        return new ProjectsUndoer(
            $repo ?? new InMemoryProjectRepository(),
            $clock ?? new FrozenClock(new DateTimeImmutable(self::UNDO_AT)),
        );
    }

    private function seedActiveProject(
        InMemoryProjectRepository $repo,
        string $name = 'Alpha',
        string $slug = 'alpha',
        ?string $description = null,
    ): Project {
        $project = Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            $name,
            $slug,
            $description,
            new DateTimeImmutable(self::CREATED_AT),
        );
        $repo->save($project);

        return $project;
    }

    /**
     * @param array<string, mixed>|null $before
     */
    private function updatedEntry(Project $project, ?array $before): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: UpdateProjectCommandHandler::AUDIT_ACTION,
            resourceType: UpdateProjectCommandHandler::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $project->id(),
            beforeState: $before,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }

    private function entryFor(string $action, Project $project): AuditEntryDto
    {
        return new AuditEntryDto(
            id: new AuditEntryId(Uuid::v7()),
            actorUserId: new UserId(Uuid::v7()),
            actorEmail: null,
            action: $action,
            resourceType: 'project',
            resourceId: (string) $project->id(),
            beforeState: null,
            afterState: null,
            metadata: [],
            occurredAt: new DateTimeImmutable(self::CREATED_AT),
            undoneAt: null,
            undoneBy: null,
            undoneByEmail: null,
            revertsEntryId: null,
        );
    }
}
