<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Application;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Application\DeactivateUserCommand;
use Modules\UserManagement\Application\DeactivateUserCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DeactivateUserCommandHandlerTest extends TestCase
{
    public function testShouldSetDeactivatedAtWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $now = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $sut = new DeactivateUserCommandHandler($profiles, $auth, $audit, new FrozenClock($now));

        $sut(new DeactivateUserCommand($userId));

        $profile = $profiles->findById($userId);
        self::assertNotNull($profile);
        self::assertTrue($profile->isDeactivated());
        self::assertEquals($now, $profile->deactivatedAt());
    }

    public function testShouldCallAuthMarkDeactivatedWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $auth = new SpyAuthApi();
        $sut = new DeactivateUserCommandHandler($profiles, $auth, new SpyAuditLogger(), new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        $sut(new DeactivateUserCommand($userId));

        self::assertCount(1, $auth->markDeactivatedCalls);
        self::assertSame((string) $userId, (string) $auth->markDeactivatedCalls[0]);
    }

    public function testShouldLogAuditEntryWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $audit = new SpyAuditLogger();
        $sut = new DeactivateUserCommandHandler($profiles, new SpyAuthApi(), $audit, new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        $sut(new DeactivateUserCommand($userId));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame('user_management.user.deactivated', $entry->action);
        self::assertSame('user', $entry->resourceType);
        self::assertSame((string) $userId, $entry->resourceId);
    }

    public function testShouldNoOpWhenAlreadyDeactivated(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $previousDeactivation = new DateTimeImmutable('2026-11-01T08:00:00+00:00');
        $profiles->save(new UserProfile(
            $userId,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $previousDeactivation,
            $previousDeactivation,
        ));
        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $sut = new DeactivateUserCommandHandler($profiles, $auth, $audit, new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        $sut(new DeactivateUserCommand($userId));

        self::assertSame([], $auth->markDeactivatedCalls);
        self::assertSame([], $audit->entries);
        self::assertEquals($previousDeactivation, $profiles->findById($userId)?->deactivatedAt());
    }

    public function testShouldThrowUserProfileNotFoundExceptionWhenUserMissing(): void
    {
        $sut = new DeactivateUserCommandHandler(
            new InMemoryUserProfileRepository(),
            new SpyAuthApi(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $this->expectException(UserProfileNotFoundException::class);

        $sut(new DeactivateUserCommand(new UserId(Uuid::v7())));
    }
}
