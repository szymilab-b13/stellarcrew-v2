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
use Modules\UserManagement\Application\ReactivateUserCommand;
use Modules\UserManagement\Application\ReactivateUserCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ReactivateUserCommandHandlerTest extends TestCase
{
    public function testShouldClearDeactivatedAtWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save($this->deactivatedProfile($userId));

        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $sut = new ReactivateUserCommandHandler($profiles, $auth, $audit, new FrozenClock(new DateTimeImmutable('2026-11-08T09:00:00+00:00')));

        $sut(new ReactivateUserCommand($userId));

        $profile = $profiles->findById($userId);
        self::assertNotNull($profile);
        self::assertFalse($profile->isDeactivated());
        self::assertNull($profile->deactivatedAt());
    }

    public function testShouldCallAuthMarkReactivatedWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save($this->deactivatedProfile($userId));

        $auth = new SpyAuthApi();
        $sut = new ReactivateUserCommandHandler($profiles, $auth, new SpyAuditLogger(), new FrozenClock(new DateTimeImmutable('2026-11-08T09:00:00+00:00')));

        $sut(new ReactivateUserCommand($userId));

        self::assertCount(1, $auth->markReactivatedCalls);
        self::assertSame((string) $userId, (string) $auth->markReactivatedCalls[0]);
    }

    public function testShouldLogAuditEntryWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save($this->deactivatedProfile($userId));

        $audit = new SpyAuditLogger();
        $sut = new ReactivateUserCommandHandler($profiles, new SpyAuthApi(), $audit, new FrozenClock(new DateTimeImmutable('2026-11-08T09:00:00+00:00')));

        $sut(new ReactivateUserCommand($userId));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame('user_management.user.reactivated', $entry->action);
        self::assertSame('user', $entry->resourceType);
        self::assertSame((string) $userId, $entry->resourceId);
    }

    public function testShouldNoOpWhenAlreadyActive(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $sut = new ReactivateUserCommandHandler($profiles, $auth, $audit, new FrozenClock(new DateTimeImmutable('2026-11-08T09:00:00+00:00')));

        $sut(new ReactivateUserCommand($userId));

        self::assertSame([], $auth->markReactivatedCalls);
        self::assertSame([], $audit->entries);
    }

    public function testShouldThrowUserProfileNotFoundExceptionWhenUserMissing(): void
    {
        $sut = new ReactivateUserCommandHandler(
            new InMemoryUserProfileRepository(),
            new SpyAuthApi(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-08T09:00:00+00:00')),
        );

        $this->expectException(UserProfileNotFoundException::class);

        $sut(new ReactivateUserCommand(new UserId(Uuid::v7())));
    }

    private function deactivatedProfile(UserId $userId): UserProfile
    {
        $previousDeactivation = new DateTimeImmutable('2026-11-01T08:00:00+00:00');

        return new UserProfile(
            $userId,
            null,
            '#abcdef',
            new DateTimeImmutable('2026-11-01T08:00:00+00:00'),
            $previousDeactivation,
            $previousDeactivation,
        );
    }
}
