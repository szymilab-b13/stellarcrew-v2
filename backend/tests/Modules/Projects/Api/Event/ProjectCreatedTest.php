<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Projects\Api\Event\ProjectCreated;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;

final class ProjectCreatedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $workspaceId = WorkspaceId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectCreated($projectId, $workspaceId, 'API Service', 'api-service', $occurredAt);

        self::assertSame($projectId, $sut->projectId);
        self::assertSame($workspaceId, $sut->workspaceId);
        self::assertSame('API Service', $sut->name);
        self::assertSame('api-service', $sut->slug);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
