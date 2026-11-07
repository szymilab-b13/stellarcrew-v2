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
        self::assertFalse($sut->isDeactivated());
        self::assertNull($sut->deactivatedAt());
    }

    public function testShouldCreateAdminViaFactoryWithAdminRole(): void
    {
        $id = new UserId(Uuid::v7());
        $createdAt = new DateTimeImmutable('2026-11-05T09:00:00+00:00');

        $sut = User::createAdmin($id, 'admin@example.com', 'hashed-password', $createdAt);

        self::assertSame(UserRole::Admin, $sut->role());
    }

    public function testShouldMarkAsDeactivatedWhenDeactivateCalled(): void
    {
        $sut = $this->freshUser();
        $now = new DateTimeImmutable('2026-11-07T18:00:00+00:00');

        $sut->deactivate($now);

        self::assertTrue($sut->isDeactivated());
        self::assertEquals($now, $sut->deactivatedAt());
    }

    public function testShouldKeepOriginalDeactivatedAtWhenDeactivateCalledTwice(): void
    {
        $sut = $this->freshUser();
        $first = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $later = new DateTimeImmutable('2026-11-08T09:00:00+00:00');

        $sut->deactivate($first);
        $sut->deactivate($later);

        self::assertEquals($first, $sut->deactivatedAt());
    }

    public function testShouldClearDeactivatedAtWhenReactivateCalled(): void
    {
        $sut = $this->freshUser();
        $sut->deactivate(new DateTimeImmutable('2026-11-07T18:00:00+00:00'));

        $sut->reactivate();

        self::assertFalse($sut->isDeactivated());
        self::assertNull($sut->deactivatedAt());
    }

    public function testShouldReplacePasswordHashWhenChangePasswordCalled(): void
    {
        $sut = $this->freshUser();

        $sut->changePassword('new-hashed-password');

        self::assertSame('new-hashed-password', $sut->passwordHash());
    }

    private function freshUser(): User
    {
        return new User(
            new UserId(Uuid::v7()),
            'admin@example.com',
            'hashed-password',
            UserRole::Admin,
            new DateTimeImmutable('2026-11-05T09:00:00+00:00'),
        );
    }
}
