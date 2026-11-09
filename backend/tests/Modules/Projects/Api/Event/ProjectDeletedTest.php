<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Projects\Api\Event\ProjectDeleted;
use Modules\Shared\Kernel\ProjectId;
use Modules\Shared\Kernel\WorkspaceId;
use PHPUnit\Framework\TestCase;

final class ProjectDeletedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $workspaceId = WorkspaceId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectDeleted($projectId, $workspaceId, $occurredAt);

        self::assertSame($projectId, $sut->projectId);
        self::assertSame($workspaceId, $sut->workspaceId);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
