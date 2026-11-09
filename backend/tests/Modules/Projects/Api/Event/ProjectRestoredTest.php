<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Projects\Api\Event\ProjectRestored;
use Modules\Shared\Kernel\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectRestoredTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectRestored($projectId, $occurredAt);

        self::assertSame($projectId, $sut->projectId);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
