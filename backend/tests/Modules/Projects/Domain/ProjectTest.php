<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Projects\Domain\Project;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class ProjectTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = ProjectId::generate();
        $workspaceId = WorkspaceId::generate();
        $now = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = Project::create($id, $workspaceId, 'API Service', 'api-service', 'Backend REST API', $now);

        self::assertSame($id, $sut->id());
        self::assertSame($workspaceId, $sut->workspaceId());
        self::assertSame('API Service', $sut->name());
        self::assertSame('api-service', $sut->slug());
        self::assertSame('Backend REST API', $sut->description());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isDeleted());
        self::assertNull($sut->deletedAt());
    }

    public function testShouldAllowNullDescriptionWhenCreated(): void
    {
        $sut = $this->makeProject();

        self::assertNull($sut->description());
    }

    public function testShouldRejectEmptyNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            '  ',
            'ok-slug',
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            str_repeat('a', Project::NAME_MAX + 1),
            'ok-slug',
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }

    public function testShouldRejectNonKebabSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'API',
            'API Service',
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }

    public function testShouldRejectEmptySlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'API',
            '',
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongDescriptionWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'API',
            'api',
            str_repeat('x', Project::DESCRIPTION_MAX + 1),
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }

    public function testShouldBumpUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeProject();

        $later = new DateTimeImmutable('2026-11-10T10:00:00+00:00');
        $sut->rename('New Name', $later);

        self::assertSame('New Name', $sut->name());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldBumpUpdatedAtWhenSlugChanged(): void
    {
        $sut = $this->makeProject();

        $later = new DateTimeImmutable('2026-11-10T10:00:00+00:00');
        $sut->changeSlug('new-slug', $later);

        self::assertSame('new-slug', $sut->slug());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldBumpUpdatedAtWhenDescriptionChanged(): void
    {
        $sut = $this->makeProject();

        $later = new DateTimeImmutable('2026-11-10T10:00:00+00:00');
        $sut->changeDescription('Updated description', $later);

        self::assertSame('Updated description', $sut->description());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldAllowClearingDescriptionWhenChanged(): void
    {
        $sut = Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'API',
            'api',
            'Initial description',
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );

        $sut->changeDescription(null, new DateTimeImmutable('2026-11-10T10:00:00+00:00'));

        self::assertNull($sut->description());
    }

    public function testShouldMarkAsDeletedWhenSoftDeleted(): void
    {
        $sut = $this->makeProject();

        $deletedAt = new DateTimeImmutable('2026-11-11T10:00:00+00:00');
        $sut->softDelete($deletedAt);

        self::assertTrue($sut->isDeleted());
        self::assertEquals($deletedAt, $sut->deletedAt());
    }

    public function testShouldIgnoreSoftDeleteWhenAlreadyDeleted(): void
    {
        $sut = $this->makeProject();
        $first = new DateTimeImmutable('2026-11-11T10:00:00+00:00');
        $sut->softDelete($first);

        $sut->softDelete(new DateTimeImmutable('2026-11-12T10:00:00+00:00'));

        self::assertEquals($first, $sut->deletedAt());
    }

    public function testShouldClearDeletedAtWhenRestored(): void
    {
        $sut = $this->makeProject();
        $sut->softDelete(new DateTimeImmutable('2026-11-11T10:00:00+00:00'));

        $restoredAt = new DateTimeImmutable('2026-11-12T10:00:00+00:00');
        $sut->restore($restoredAt);

        self::assertFalse($sut->isDeleted());
        self::assertNull($sut->deletedAt());
        self::assertEquals($restoredAt, $sut->updatedAt());
    }

    public function testShouldIgnoreRestoreWhenNotDeleted(): void
    {
        $sut = $this->makeProject();
        $originalUpdatedAt = $sut->updatedAt();

        $sut->restore(new DateTimeImmutable('2026-11-12T10:00:00+00:00'));

        self::assertFalse($sut->isDeleted());
        self::assertEquals($originalUpdatedAt, $sut->updatedAt());
    }

    private function makeProject(): Project
    {
        return Project::create(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'API',
            'api',
            null,
            new DateTimeImmutable('2026-11-09T10:00:00+00:00'),
        );
    }
}
