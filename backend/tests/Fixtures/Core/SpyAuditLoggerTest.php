<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use DateTimeImmutable;
use LogicException;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SpyAuditLoggerTest extends TestCase
{
    public function testShouldThrowLogicExceptionWhenUserIdIsNull(): void
    {
        $sut = new SpyAuditLogger();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('AuditService cannot persist entry without actor user id.');

        $sut->log(new AuditEntry(
            userId: null,
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-07T18:00:00+00:00'),
            resourceType: 'user',
            resourceId: (string) Uuid::v7(),
        ));
    }

    public function testShouldRecordEntryWhenUserIdPresent(): void
    {
        $sut = new SpyAuditLogger();
        $actorId = new UserId(Uuid::v7());

        $sut->log(new AuditEntry(
            userId: $actorId,
            action: 'user_management.user.deactivated',
            occurredAt: new DateTimeImmutable('2026-11-07T18:00:00+00:00'),
            resourceType: 'user',
            resourceId: (string) Uuid::v7(),
        ));

        self::assertCount(1, $sut->entries);
        self::assertSame($actorId, $sut->entries[0]->userId);
    }
}
