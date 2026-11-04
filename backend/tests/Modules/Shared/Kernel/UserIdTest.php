<?php

declare(strict_types=1);

namespace App\Tests\Modules\Shared\Kernel;

use InvalidArgumentException;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class UserIdTest extends TestCase
{
    public function testShouldGenerateUuidV7WhenCallingGenerate(): void
    {
        $sut = UserId::generate();

        self::assertInstanceOf(UuidV7::class, $sut->value);
    }

    public function testShouldHydrateFromStringWhenValidUuidProvided(): void
    {
        $uuid = Uuid::v7();

        $sut = UserId::fromString((string) $uuid);

        self::assertTrue($sut->value->equals($uuid));
    }

    public function testShouldRejectWhenInvalidUuidProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UserId::fromString('not-a-uuid');
    }

    public function testShouldBeEqualWhenSameUuid(): void
    {
        $uuid = Uuid::v7();
        $a = UserId::fromString((string) $uuid);
        $b = UserId::fromString((string) $uuid);

        $result = $a->equals($b);

        self::assertTrue($result);
    }

    public function testShouldNotBeEqualWhenDifferentUuid(): void
    {
        $a = UserId::generate();
        $b = UserId::generate();

        $result = $a->equals($b);

        self::assertFalse($result);
    }

    public function testShouldCastToStringWhenCastedToString(): void
    {
        $uuid = Uuid::v7();
        $sut = UserId::fromString((string) $uuid);

        $result = (string) $sut;

        self::assertSame((string) $uuid, $result);
    }
}
