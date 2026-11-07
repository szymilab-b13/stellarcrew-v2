<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Application;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use App\Tests\Fixtures\UserManagement\InMemoryUserProfileRepository;
use DateTimeImmutable;
use Modules\Auth\Api\Exception\EmailTakenException;
use Modules\Shared\Kernel\FrozenClock;
use Modules\UserManagement\Application\CreateUserCommand;
use Modules\UserManagement\Application\CreateUserCommandHandler;
use Modules\UserManagement\Domain\AvatarColorPicker;
use PHPUnit\Framework\TestCase;

final class CreateUserCommandHandlerTest extends TestCase
{
    public function testShouldCreateAuthUserAndUserProfileWhenCommandExecuted(): void
    {
        $auth = new SpyAuthApi();
        $profiles = new InMemoryUserProfileRepository();
        $sut = new CreateUserCommandHandler(
            $auth,
            $profiles,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T14:00:00+00:00')),
        );

        $dto = $sut(new CreateUserCommand('new@example.com', 'long-enough-password', false));

        self::assertCount(1, $auth->createUserCalls);
        self::assertSame('new@example.com', $auth->createUserCalls[0]['email']);
        self::assertSame('long-enough-password', $auth->createUserCalls[0]['password']);
        self::assertFalse($auth->createUserCalls[0]['isSuperAdmin']);
        self::assertSame(1, $profiles->count());
        self::assertSame($dto->id, (string) $profiles->all()[0]->id());
    }

    public function testShouldAssignAvatarColorFromEmailWhenCreatingProfile(): void
    {
        $profiles = new InMemoryUserProfileRepository();
        $sut = new CreateUserCommandHandler(
            new SpyAuthApi(),
            $profiles,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T14:00:00+00:00')),
        );

        $sut(new CreateUserCommand('deterministic@example.com', 'long-enough-password'));

        self::assertSame(AvatarColorPicker::pick('deterministic@example.com'), $profiles->all()[0]->avatarColor());
    }

    public function testShouldLogAuditEntryWhenUserCreated(): void
    {
        $audit = new SpyAuditLogger();
        $sut = new CreateUserCommandHandler(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T14:00:00+00:00')),
        );

        $sut(new CreateUserCommand('audited@example.com', 'long-enough-password', true));

        self::assertCount(1, $audit->entries);
        $entry = $audit->entries[0];
        self::assertSame('user_management.user.created', $entry->action);
        self::assertSame('user', $entry->resourceType);
        self::assertSame('audited@example.com', $entry->metadata['email']);
        self::assertTrue($entry->metadata['is_super_admin']);
    }

    public function testShouldPropagateEmailTakenExceptionWhenAuthRejects(): void
    {
        $auth = (new SpyAuthApi())->withTakenEmail('taken@example.com');
        $profiles = new InMemoryUserProfileRepository();
        $audit = new SpyAuditLogger();
        $sut = new CreateUserCommandHandler(
            $auth,
            $profiles,
            $audit,
            new FrozenClock(new DateTimeImmutable('2026-11-07T14:00:00+00:00')),
        );

        try {
            $sut(new CreateUserCommand('taken@example.com', 'long-enough-password'));
            self::fail('Expected EmailTakenException');
        } catch (EmailTakenException) {
            self::assertSame(0, $profiles->count());
            self::assertSame([], $audit->entries);
        }
    }

    public function testShouldReturnUserProfileDtoWhenCreated(): void
    {
        $sut = new CreateUserCommandHandler(
            new SpyAuthApi(),
            new InMemoryUserProfileRepository(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable('2026-11-07T14:00:00+00:00')),
        );

        $dto = $sut(new CreateUserCommand('dto@example.com', 'long-enough-password'));

        self::assertNotEmpty($dto->id);
        self::assertNull($dto->displayName);
        self::assertSame(AvatarColorPicker::pick('dto@example.com'), $dto->avatarColor);
        self::assertFalse($dto->isDeactivated);
    }
}
