<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Agents\Api\Event\AgentRenamed;
use Modules\Shared\Kernel\AgentId;
use PHPUnit\Framework\TestCase;

final class AgentRenamedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $agentId = AgentId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-13T10:00:00+00:00');

        $sut = new AgentRenamed($agentId, 'Maja', 'Maja PM', $occurredAt);

        self::assertSame($agentId, $sut->agentId);
        self::assertSame('Maja', $sut->oldName);
        self::assertSame('Maja PM', $sut->newName);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
