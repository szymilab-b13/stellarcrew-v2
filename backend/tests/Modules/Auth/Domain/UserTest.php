<?php

declare(strict_types=1);

namespace App\Tests\Modules\Auth\Domain;

use DateTimeImmutable;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRole;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class UserTest extends TestCase
{
    public function testShouldExposeAllFieldsWhenConstructed(): void
    {
        $id = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-05T09:00:00+00:00');

        $sut = new User($id, 'admin@example.com', 'hashed-password', UserRole::Admin, $createdAt);

        self::assertSame($id, $sut->id());
        self::assertSame('admin@example.com', $sut->email());
        self::assertSame('hashed-password', $sut->passwordHash());
        self::assertSame(UserRole::Admin, $sut->role());
        self::assertEquals($createdAt, $sut->createdAt());
    }

    public function testShouldCreateAdminViaFactoryWithAdminRole(): void
    {
        $id = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-05T09:00:00+00:00');

        $sut = User::createAdmin($id, 'admin@example.com', 'hashed-password', $createdAt);

        self::assertSame(UserRole::Admin, $sut->role());
    }
}
