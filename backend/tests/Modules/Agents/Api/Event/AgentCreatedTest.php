<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api\Event;

use DateTimeImmutable;
use Modules\Agents\Api\Event\AgentCreated;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class AgentCreatedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $agentId = AgentId::generate();
        $organizationId = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-13T10:00:00+00:00');

        $sut = new AgentCreated($agentId, $organizationId, $ownerUserId, 'Maja', 'maja', $occurredAt);

        self::assertSame($agentId, $sut->agentId);
        self::assertSame($organizationId, $sut->organizationId);
        self::assertSame($ownerUserId, $sut->ownerUserId);
        self::assertSame('Maja', $sut->name);
        self::assertSame('maja', $sut->slug);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
