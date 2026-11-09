<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Infrastructure\EventListener;

use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\Projects\InMemoryProjectRepository;
use DateTimeImmutable;
use Modules\Projects\Application\DeleteProjectCommandHandler;
use Modules\Projects\Domain\Project;
use Modules\Projects\Infrastructure\EventListener\CascadeWorkspaceDeletedListener;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkspaceId;
use Modules\Workspaces\Api\Event\WorkspaceDeleted;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class CascadeWorkspaceDeletedListenerTest extends TestCase
{
    public function testShouldSoftDeleteAllActiveProjectsInWorkspace(): void
    {
        $repo = new InMemoryProjectRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-01T09:00:00+00:00');
        $deletedAt = new DateTimeImmutable('2026-11-09T12:00:00+00:00');
        $projectA = Project::create(ProjectId::generate(), $workspaceId, 'API', 'api', null, $createdAt);
        $projectB = Project::create(ProjectId::generate(), $workspaceId, 'App', 'app', 'App bundle', $createdAt);
        $repo->save($projectA);
        $repo->save($projectB);

        $sut = new CascadeWorkspaceDeletedListener($repo, new SpyAuditLogger());

        $sut(new WorkspaceDeleted($workspaceId, new UserId(Uuid::v7()), $deletedAt));

        $afterA = $repo->findById($projectA->id());
        $afterB = $repo->findById($projectB->id());
        self::assertInstanceOf(Project::class, $afterA);
        self::assertInstanceOf(Project::class, $afterB);
        self::assertTrue($afterA->isDeleted());
        self::assertTrue($afterB->isDeleted());
        self::assertSame($deletedAt, $afterA->deletedAt());
        self::assertSame($deletedAt, $afterB->deletedAt());
    }

    public function testShouldRecordPerProjectAuditEntriesWithCascadeMetadata(): void
    {
        $repo = new InMemoryProjectRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $actorId = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-01T09:00:00+00:00');
        $deletedAt = new DateTimeImmutable('2026-11-09T12:00:00+00:00');
        $projectA = Project::create(ProjectId::generate(), $workspaceId, 'API', 'api', 'A description', $createdAt);
        $projectB = Project::create(ProjectId::generate(), $workspaceId, 'App', 'app', null, $createdAt);
        $repo->save($projectA);
        $repo->save($projectB);

        $audit = new SpyAuditLogger();
        $sut = new CascadeWorkspaceDeletedListener($repo, $audit);

        $sut(new WorkspaceDeleted($workspaceId, $actorId, $deletedAt));

        self::assertCount(2, $audit->entries);
        $byResource = [];
        foreach ($audit->entries as $entry) {
            $byResource[$entry->resourceId] = $entry;
        }

        $entryA = $byResource[(string) $projectA->id()];
        $entryB = $byResource[(string) $projectB->id()];
        self::assertSame(DeleteProjectCommandHandler::AUDIT_ACTION, $entryA->action);
        self::assertSame(DeleteProjectCommandHandler::AUDIT_RESOURCE_TYPE, $entryA->resourceType);
        self::assertSame($deletedAt, $entryA->occurredAt);
        self::assertTrue($entryA->userId?->equals($actorId) ?? false);
        self::assertSame('API', $entryA->metadata['before_state']['name']);
        self::assertSame('api', $entryA->metadata['before_state']['slug']);
        self::assertSame('A description', $entryA->metadata['before_state']['description']);
        self::assertSame((string) $workspaceId, $entryA->metadata['cascade']['workspace_id']);
        self::assertSame(CascadeWorkspaceDeletedListener::CASCADE_REASON, $entryA->metadata['cascade']['reason']);
        self::assertSame('App', $entryB->metadata['before_state']['name']);
        self::assertNull($entryB->metadata['before_state']['description']);
    }

    public function testShouldDoNothingWhenWorkspaceHasNoProjects(): void
    {
        $repo = new InMemoryProjectRepository();
        $audit = new SpyAuditLogger();
        $sut = new CascadeWorkspaceDeletedListener($repo, $audit);

        $sut(new WorkspaceDeleted(
            new WorkspaceId(Uuid::v7()),
            new UserId(Uuid::v7()),
            new DateTimeImmutable('2026-11-09T12:00:00+00:00'),
        ));

        self::assertSame([], $audit->entries);
    }

    public function testShouldIgnoreAlreadySoftDeletedProjects(): void
    {
        $repo = new InMemoryProjectRepository();
        $workspaceId = new WorkspaceId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-01T09:00:00+00:00');
        $priorDeletion = new DateTimeImmutable('2026-11-05T08:00:00+00:00');
        $deletedAt = new DateTimeImmutable('2026-11-09T12:00:00+00:00');
        $active = Project::create(ProjectId::generate(), $workspaceId, 'API', 'api', null, $createdAt);
        $alreadyDeleted = Project::create(ProjectId::generate(), $workspaceId, 'Legacy', 'legacy', null, $createdAt);
        $alreadyDeleted->softDelete($priorDeletion);

        $repo->save($active);
        $repo->save($alreadyDeleted);

        $audit = new SpyAuditLogger();
        $sut = new CascadeWorkspaceDeletedListener($repo, $audit);

        $sut(new WorkspaceDeleted($workspaceId, new UserId(Uuid::v7()), $deletedAt));

        self::assertCount(1, $audit->entries);
        self::assertSame((string) $active->id(), $audit->entries[0]->resourceId);
        $legacyAfter = $repo->findById($alreadyDeleted->id());
        self::assertInstanceOf(Project::class, $legacyAfter);
        self::assertSame($priorDeletion, $legacyAfter->deletedAt());
    }

    public function testShouldOnlyAffectProjectsInsideDeletedWorkspace(): void
    {
        $repo = new InMemoryProjectRepository();
        $workspaceA = new WorkspaceId(Uuid::v7());
        $workspaceB = new WorkspaceId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-01T09:00:00+00:00');
        $deletedAt = new DateTimeImmutable('2026-11-09T12:00:00+00:00');
        $insideA = Project::create(ProjectId::generate(), $workspaceA, 'API', 'api', null, $createdAt);
        $outsideB = Project::create(ProjectId::generate(), $workspaceB, 'Other', 'other', null, $createdAt);
        $repo->save($insideA);
        $repo->save($outsideB);

        $audit = new SpyAuditLogger();
        $sut = new CascadeWorkspaceDeletedListener($repo, $audit);

        $sut(new WorkspaceDeleted($workspaceA, new UserId(Uuid::v7()), $deletedAt));

        $afterA = $repo->findById($insideA->id());
        $afterB = $repo->findById($outsideB->id());
        self::assertInstanceOf(Project::class, $afterA);
        self::assertInstanceOf(Project::class, $afterB);
        self::assertTrue($afterA->isDeleted());
        self::assertFalse($afterB->isDeleted());
        self::assertCount(1, $audit->entries);
        self::assertSame((string) $insideA->id(), $audit->entries[0]->resourceId);
    }
}
