<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Application;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use DateTimeImmutable;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Api\Exception\PasswordTooShortException;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Application\ResetUserPasswordCommand;
use Modules\UserManagement\Application\ResetUserPasswordCommandHandler;
use Modules\UserManagement\Domain\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ResetUserPasswordCommandHandlerTest extends TestCase
{
    public function testShouldCallAuthSetPasswordWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $now = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $sut = new ResetUserPasswordCommandHandler($profiles, $auth, $audit, new FrozenClock($now));

        $sut(new ResetUserPasswordCommand($userId, 'brand-new-password'));

        self::assertCount(1, $auth->setPasswordCalls);
        self::assertSame((string) $userId, (string) $auth->setPasswordCalls[0]['userId']);
        self::assertSame('brand-new-password', $auth->setPasswordCalls[0]['plaintext']);
    }

    public function testShouldLogAuditEntryWithoutBeforeStateWhenCommandExecuted(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $audit = new SpyAuditLogger();
        $now = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $sut = new ResetUserPasswordCommandHandler($profiles, new SpyAuthApi(), $audit, new FrozenClock($now));

        $sut(new ResetUserPasswordCommand($userId, 'brand-new-password'));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame('user_management.user.password_reset', $entry->action);
        self::assertSame('user', $entry->resourceType);
        self::assertSame((string) $userId, $entry->resourceId);
        self::assertEquals($now, $entry->occurredAt);
    }

    public function testShouldThrowPasswordTooShortExceptionWhenPasswordUnder12Chars(): void
    {
        $userId = new UserId(Uuid::v7());
        $profiles = new InMemoryUserProfileRepository();
        $profiles->save(UserProfile::create($userId, null, '#abcdef', new DateTimeImmutable('2026-11-07T14:00:00+00:00')));

        $auth = new SpyAuthApi();
        $audit = new SpyAuditLogger();
        $sut = new ResetUserPasswordCommandHandler($profiles, $auth, $audit, new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')));

        try {
            $sut(new ResetUserPasswordCommand($userId, 'too-short'));
            self::fail('Expected PasswordTooShortException');
        } catch (PasswordTooShortException) {
            self::assertSame([], $auth->setPasswordCalls);
            self::assertSame([], $audit->entries);
        }
    }

    public function testShouldThrowUserProfileNotFoundExceptionWhenUserMissing(): void
    {
        $sut = new ResetUserPasswordCommandHandler(
            new InMemoryUserProfileRepository(),
            new SpyAuthApi(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $this->expectException(UserProfileNotFoundException::class);

        $sut(new ResetUserPasswordCommand(new UserId(Uuid::v7()), 'brand-new-password'));
    }
}
