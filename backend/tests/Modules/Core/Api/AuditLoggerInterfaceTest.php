<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Api;

use DateTimeImmutable;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use PHPUnit\Framework\TestCase;

final class AuditLoggerInterfaceTest extends TestCase
{
    public function testShouldAcceptAuditEntryWhenImplementationLogs(): void
    {
        $sut = new class () implements AuditLoggerInterface {
            /** @var list<AuditEntry> */
            public array $entries = [];

            public function log(AuditEntry $entry): void
            {
                $this->entries[] = $entry;
            }
        };
        $entry = new AuditEntry(
            userId: null,
            action: 'system.ping',
            occurredAt: new DateTimeImmutable('2026-11-04T15:30:00+00:00'),
        );

        $sut->log($entry);

        self::assertSame([$entry], $sut->entries);
    }
}
