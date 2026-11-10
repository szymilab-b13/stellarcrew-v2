<?php

declare(strict_types=1);

namespace App\Tests\Modules\Organizations\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Organizations\Domain\Organization;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class OrganizationTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $now = new DateTimeImmutable('2026-11-10T10:00:00+00:00');

        $sut = Organization::create($id, "Marek's Organization", 'marek-organization', $ownerUserId, $now);

        self::assertSame($id, $sut->id());
        self::assertSame("Marek's Organization", $sut->name());
        self::assertSame('marek-organization', $sut->slug());
        self::assertSame($ownerUserId, $sut->ownerUserId());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isDeleted());
        self::assertNull($sut->deletedAt());
    }

    public function testShouldRejectEmptyNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(
            OrganizationId::generate(),
            '  ',
            'ok-slug',
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(
            OrganizationId::generate(),
            str_repeat('a', Organization::NAME_MAX + 1),
            'ok-slug',
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }

    public function testShouldRejectEmptySlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(
            OrganizationId::generate(),
            'Acme',
            '',
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }

    public function testShouldRejectNonKebabSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(
            OrganizationId::generate(),
            'Acme',
            'Acme Corp',
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongSlugWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Organization::create(
            OrganizationId::generate(),
            'Acme',
            str_repeat('a', Organization::SLUG_MAX + 1),
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }

    public function testShouldBumpUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeOrganization();

        $later = new DateTimeImmutable('2026-11-11T10:00:00+00:00');
        $sut->rename('New Name', $later);

        self::assertSame('New Name', $sut->name());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldBumpUpdatedAtWhenSlugChanged(): void
    {
        $sut = $this->makeOrganization();

        $later = new DateTimeImmutable('2026-11-11T10:00:00+00:00');
        $sut->changeSlug('new-slug', $later);

        self::assertSame('new-slug', $sut->slug());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldMarkAsDeletedWhenSoftDeleted(): void
    {
        $sut = $this->makeOrganization();

        $deletedAt = new DateTimeImmutable('2026-11-12T10:00:00+00:00');
        $sut->softDelete($deletedAt);

        self::assertTrue($sut->isDeleted());
        self::assertEquals($deletedAt, $sut->deletedAt());
    }

    public function testShouldIgnoreSoftDeleteWhenAlreadyDeleted(): void
    {
        $sut = $this->makeOrganization();
        $first = new DateTimeImmutable('2026-11-12T10:00:00+00:00');
        $sut->softDelete($first);

        $sut->softDelete(new DateTimeImmutable('2026-11-13T10:00:00+00:00'));

        self::assertEquals($first, $sut->deletedAt());
    }

    public function testShouldClearDeletedAtWhenRestored(): void
    {
        $sut = $this->makeOrganization();
        $sut->softDelete(new DateTimeImmutable('2026-11-12T10:00:00+00:00'));

        $restoredAt = new DateTimeImmutable('2026-11-13T10:00:00+00:00');
        $sut->restore($restoredAt);

        self::assertFalse($sut->isDeleted());
        self::assertNull($sut->deletedAt());
        self::assertEquals($restoredAt, $sut->updatedAt());
    }

    public function testShouldIgnoreRestoreWhenNotDeleted(): void
    {
        $sut = $this->makeOrganization();
        $originalUpdatedAt = $sut->updatedAt();

        $sut->restore(new DateTimeImmutable('2026-11-13T10:00:00+00:00'));

        self::assertFalse($sut->isDeleted());
        self::assertEquals($originalUpdatedAt, $sut->updatedAt());
    }

    private function makeOrganization(): Organization
    {
        return Organization::create(
            OrganizationId::generate(),
            'Acme',
            'acme',
            UserId::generate(),
            new DateTimeImmutable('2026-11-10T10:00:00+00:00'),
        );
    }
}
