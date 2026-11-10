<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Api\Dto;

use DateTimeImmutable;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

final class OrganizationViewTest extends TestCase
{
    public function testShouldExposeFieldsWhenConstructed(): void
    {
        $organizationId = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $createdAt = new DateTimeImmutable('2026-11-10T10:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-11-10T11:00:00+00:00');

        $sut = new OrganizationView(
            $organizationId,
            'Boss Organization',
            'boss-organization',
            $ownerUserId,
            $createdAt,
            $updatedAt,
        );

        self::assertSame($organizationId, $sut->id);
        self::assertSame('Boss Organization', $sut->name);
        self::assertSame('boss-organization', $sut->slug);
        self::assertSame($ownerUserId, $sut->ownerUserId);
        self::assertSame($createdAt, $sut->createdAt);
        self::assertSame($updatedAt, $sut->updatedAt);
    }
}
