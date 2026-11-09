<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Projects\Api\Event\ProjectRenamed;
use Modules\Shared\Kernel\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectRenamedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectRenamed($projectId, 'Old Name', 'New Name', $occurredAt);

        self::assertSame($projectId, $sut->projectId);
        self::assertSame('Old Name', $sut->oldName);
        self::assertSame('New Name', $sut->newName);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
