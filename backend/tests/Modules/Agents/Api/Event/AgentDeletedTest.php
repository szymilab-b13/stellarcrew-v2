<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Agents\Api\Event\AgentDeleted;
use Modules\Shared\Kernel\AgentId;
use PHPUnit\Framework\TestCase;

final class AgentDeletedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $agentId = AgentId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-13T10:00:00+00:00');

        $sut = new AgentDeleted($agentId, $occurredAt);

        self::assertSame($agentId, $sut->agentId);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
