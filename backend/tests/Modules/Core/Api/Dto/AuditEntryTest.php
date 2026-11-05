<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Api\Dto;

use DateTimeImmutable;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class AuditEntryTest extends TestCase
{
    public function testShouldExposeAllFieldsWhenFullyConstructed(): void
    {
        $userId = UserId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-04T15:30:00+00:00');

        $sut = new AuditEntry(
            userId: $userId,
            action: 'auth.user.login.success',
            occurredAt: $occurredAt,
            resourceType: 'User',
            resourceId: '018fef01-3f76-7fff-8000-000000000001',
            metadata: ['method' => 'password', 'remember' => true],
            ip: '10.0.0.1',
            userAgent: 'Mozilla/5.0',
        );

        self::assertSame($userId, $sut->userId);
        self::assertSame('auth.user.login.success', $sut->action);
        self::assertSame($occurredAt, $sut->occurredAt);
        self::assertSame('User', $sut->resourceType);
        self::assertSame('018fef01-3f76-7fff-8000-000000000001', $sut->resourceId);
        self::assertSame(['method' => 'password', 'remember' => true], $sut->metadata);
        self::assertSame('10.0.0.1', $sut->ip);
        self::assertSame('Mozilla/5.0', $sut->userAgent);
    }

    public function testShouldDefaultOptionalFieldsWhenMinimallyConstructed(): void
    {
        $occurredAt = new DateTimeImmutable('2026-11-04T15:30:00+00:00');

        $sut = new AuditEntry(
            userId: null,
            action: 'system.scheduler.tick',
            occurredAt: $occurredAt,
        );

        self::assertNull($sut->userId);
        self::assertSame('system.scheduler.tick', $sut->action);
        self::assertSame($occurredAt, $sut->occurredAt);
        self::assertNull($sut->resourceType);
        self::assertNull($sut->resourceId);
        self::assertSame([], $sut->metadata);
        self::assertNull($sut->ip);
        self::assertNull($sut->userAgent);
    }
}
