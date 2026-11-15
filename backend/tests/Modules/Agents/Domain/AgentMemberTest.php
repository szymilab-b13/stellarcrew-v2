<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Domain;

use DateTimeImmutable;
use Modules\Agents\Domain\AgentMember;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class AgentMemberTest extends TestCase
{
    public function testShouldExposeFieldsWhenGranted(): void
    {
        $agentId = AgentId::generate();
        $userId = UserId::generate();
        $grantedBy = UserId::generate();
        $grantedAt = new DateTimeImmutable('2026-11-13T15:00:00+00:00');

        $sut = AgentMember::grant($agentId, $userId, $grantedBy, $grantedAt);

        self::assertTrue($sut->agentId()->equals($agentId));
        self::assertTrue($sut->userId()->equals($userId));
        self::assertTrue($sut->grantedBy()->equals($grantedBy));
        self::assertEquals($grantedAt, $sut->grantedAt());
    }

    public function testShouldAssignFreshSurrogateIdWhenGranted(): void
    {
        $agentId = AgentId::generate();
        $userId = UserId::generate();
        $grantedBy = UserId::generate();
        $grantedAt = new DateTimeImmutable('2026-11-13T15:00:00+00:00');

        $first = AgentMember::grant($agentId, $userId, $grantedBy, $grantedAt);
        $second = AgentMember::grant($agentId, $userId, $grantedBy, $grantedAt);

        self::assertFalse($first->id()->equals($second->id()));
    }
}
