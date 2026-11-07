<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Domain\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserProfileTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = new UserId(Uuid::v7());
        $now = new DateTimeImmutable('2026-11-07T10:00:00+00:00');

        $sut = UserProfile::create($id, null, '#3B82F6', $now);

        self::assertSame($id, $sut->id());
        self::assertNull($sut->displayName());
        self::assertSame('#3B82F6', $sut->avatarColor());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertNull($sut->deactivatedAt());
        self::assertFalse($sut->isDeactivated());
    }

    public function testShouldStoreDisplayNameWhenProvided(): void
    {
        $sut = UserProfile::create(
            new UserId(Uuid::v7()),
            'Tomek',
            '#EF4444',
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
        );

        self::assertSame('Tomek', $sut->displayName());
    }

    public function testShouldRejectWhitespaceDisplayNameWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserProfile::create(
            new UserId(Uuid::v7()),
            '   ',
            '#EF4444',
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
        );
    }

    public function testShouldRejectDisplayNameOverLimitWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserProfile::create(
            new UserId(Uuid::v7()),
            str_repeat('a', UserProfile::DISPLAY_NAME_MAX + 1),
            '#EF4444',
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
        );
    }

    public function testShouldRejectInvalidAvatarColorWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserProfile::create(
            new UserId(Uuid::v7()),
            null,
            'blue',
            new DateTimeImmutable('2026-11-07T10:00:00+00:00'),
        );
    }
}
