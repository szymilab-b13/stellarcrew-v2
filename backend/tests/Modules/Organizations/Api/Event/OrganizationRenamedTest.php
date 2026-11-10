<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Organizations\Api\Event\OrganizationRenamed;
use Modules\Shared\Kernel\OrganizationId;
use PHPUnit\Framework\TestCase;

final class OrganizationRenamedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $organizationId = OrganizationId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-10T10:00:00+00:00');

        $sut = new OrganizationRenamed($organizationId, 'Old Name', 'New Name', $occurredAt);

        self::assertSame($organizationId, $sut->organizationId);
        self::assertSame('Old Name', $sut->oldName);
        self::assertSame('New Name', $sut->newName);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
