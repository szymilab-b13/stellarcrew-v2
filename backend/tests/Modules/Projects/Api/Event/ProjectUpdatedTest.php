<?php

declare(strict_types=1);

namespace App\Tests\Modules\Projects\Api\Event;

use DateTimeImmutable;
use Modules\Projects\Api\Event\ProjectUpdated;
use Modules\Shared\Kernel\ProjectId;
use PHPUnit\Framework\TestCase;

final class ProjectUpdatedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $projectId = ProjectId::generate();
        $before = ['description' => null];
        $after = ['description' => 'Updated description'];
        $occurredAt = new DateTimeImmutable('2026-11-09T10:00:00+00:00');

        $sut = new ProjectUpdated($projectId, $before, $after, $occurredAt);

        self::assertSame($projectId, $sut->projectId);
        self::assertSame($before, $sut->before);
        self::assertSame($after, $sut->after);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
