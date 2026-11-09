<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Dto;

use DateTimeImmutable;
use Modules\Projects\Api\Dto\ProjectView;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;

final class ProjectViewTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $workspaceId = WorkspaceId::generate();
        $createdAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-11-09T11:00:00+00:00');

        $sut = new ProjectView(
            $projectId,
            $workspaceId,
            'API Service',
            'api-service',
            'Backend REST API',
            $createdAt,
            $updatedAt,
        );

        self::assertSame($projectId, $sut->id);
        self::assertSame($workspaceId, $sut->workspaceId);
        self::assertSame('API Service', $sut->name);
        self::assertSame('api-service', $sut->slug);
        self::assertSame('Backend REST API', $sut->description);
        self::assertSame($createdAt, $sut->createdAt);
        self::assertSame($updatedAt, $sut->updatedAt);
    }

    public function testShouldAllowNullDescriptionWhenConstructed(): void
    {
        $now = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectView(
            ProjectId::generate(),
            WorkspaceId::generate(),
            'Backend',
            'backend',
            null,
            $now,
            $now,
        );

        self::assertNull($sut->description);
    }
}
