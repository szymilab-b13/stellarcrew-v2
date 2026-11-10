<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Organizations\Api\Event\OrganizationRestored;
use Modules\Shared\Kernel\OrganizationId;
use PHPUnit\Framework\TestCase;

final class OrganizationRestoredTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $organizationId = OrganizationId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-10T10:00:00+00:00');

        $sut = new OrganizationRestored($organizationId, $occurredAt);

        self::assertSame($organizationId, $sut->organizationId);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
