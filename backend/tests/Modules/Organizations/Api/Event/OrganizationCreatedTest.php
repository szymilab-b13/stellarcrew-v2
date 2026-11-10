<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Api\Event;

use DateTimeImmutable;
use Modules\Organizations\Api\Event\OrganizationCreated;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class OrganizationCreatedTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $organizationId = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $occurredAt = new DateTimeImmutable('2026-11-10T10:00:00+00:00');

        $sut = new OrganizationCreated($organizationId, $ownerUserId, 'Boss Organization', 'boss-organization', $occurredAt);

        self::assertSame($organizationId, $sut->organizationId);
        self::assertSame($ownerUserId, $sut->ownerUserId);
        self::assertSame('Boss Organization', $sut->name);
        self::assertSame('boss-organization', $sut->slug);
        self::assertSame($occurredAt, $sut->occurredAt);
    }
}
