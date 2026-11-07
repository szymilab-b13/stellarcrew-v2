<?php

declare(strict_types=1);

namespace App\Tests\Modules\Auth\Infrastructure;

use function count;

use DateTimeImmutable;
use Modules\Auth\Api\Exception\UserNotFoundException;
use Modules\Auth\Application\PasswordHasher;
use Modules\Auth\Domain\User;
use Modules\Auth\Domain\UserRepository;
use Modules\Auth\Domain\UserRole;
use Modules\Auth\Infrastructure\DefaultAuthApi;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\UuidGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DefaultAuthApiTest extends TestCase
{
    public function testShouldPersistAdminWithHashedPasswordWhenCreated(): void
    {
        $users = $this->userRepository();
        $sut = new DefaultAuthApi(
            $users,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::fromString('0192f3f5-0000-7000-8000-000000000001')),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $sut->createAdmin('admin@example.com', 'secret-pass-long-enough');

        $admin = $users->findByEmail('admin@example.com');
        self::assertNotNull($admin);
        self::assertSame('admin@example.com', $admin->email());
        self::assertSame('hashed:secret-pass-long-enough', $admin->passwordHash());
        self::assertSame(UserRole::Admin, $admin->role());
    }

    public function testShouldReturnAuthUserDtoWithIdentityFromGenerator(): void
    {
        $uuid = Uuid::fromString('0192f3f5-0000-7000-8000-000000000001');
        $sut = new DefaultAuthApi(
            $this->userRepository(),
            $this->passwordHasher(),
            $this->uuidGenerator($uuid),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $result = $sut->createAdmin('admin@example.com', 'secret-pass-long-enough');

        self::assertSame((string) $uuid, (string) $result->id);
        self::assertSame('admin@example.com', $result->email);
        self::assertEquals(new DateTimeImmutable('2026-11-05T09:00:00+00:00'), $result->createdAt);
    }

    public function testShouldDeactivateExistingUserAndPersistTimestampWhenMarkDeactivatedCalled(): void
    {
        $users = $this->userRepository();
        $frozen = new DateTimeImmutable('2026-11-07T18:00:00+00:00');
        $sut = new DefaultAuthApi(
            $users,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::fromString('0192f3f5-0000-7000-8000-000000000002')),
            new FrozenClock($frozen),
        );
        $created = $sut->createAdmin('admin@example.com', 'secret-pass-long-enough');

        $sut->markDeactivated($created->id);

        $persisted = $users->findByEmail('admin@example.com');
        self::assertNotNull($persisted);
        self::assertTrue($persisted->isDeactivated());
        self::assertEquals($frozen, $persisted->deactivatedAt());
    }

    public function testShouldReactivateDeactivatedUserWhenMarkReactivatedCalled(): void
    {
        $users = $this->userRepository();
        $sut = new DefaultAuthApi(
            $users,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::fromString('0192f3f5-0000-7000-8000-000000000003')),
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );
        $created = $sut->createAdmin('admin@example.com', 'secret-pass-long-enough');
        $sut->markDeactivated($created->id);

        $sut->markReactivated($created->id);

        $persisted = $users->findByEmail('admin@example.com');
        self::assertNotNull($persisted);
        self::assertFalse($persisted->isDeactivated());
    }

    public function testShouldThrowUserNotFoundExceptionWhenMarkingUnknownUser(): void
    {
        $sut = new DefaultAuthApi(
            $this->userRepository(),
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::fromString('0192f3f5-0000-7000-8000-000000000004')),
            new FrozenClock(new DateTimeImmutable('2026-11-07T18:00:00+00:00')),
        );

        $this->expectException(UserNotFoundException::class);

        $sut->markDeactivated(new UserId(Uuid::v7()));
    }

    private function userRepository(): UserRepository
    {
        return new class () implements UserRepository {
            /** @var array<string, User> */
            private array $byEmail = [];

            /** @var array<string, User> */
            private array $byId = [];

            public function save(User $user): void
            {
                $this->byEmail[$user->email()] = $user;
                $this->byId[(string) $user->id()] = $user;
            }

            public function findByEmail(string $email): ?User
            {
                return $this->byEmail[$email] ?? null;
            }

            public function findById(UserId $id): ?User
            {
                return $this->byId[(string) $id] ?? null;
            }

            public function count(): int
            {
                return count($this->byEmail);
            }
        };
    }

    private function passwordHasher(): PasswordHasher
    {
        return new class () implements PasswordHasher {
            public function hash(string $plaintext): string
            {
                return 'hashed:' . $plaintext;
            }
        };
    }

    private function uuidGenerator(Uuid $uuid): UuidGenerator
    {
        return new readonly class ($uuid) implements UuidGenerator {
            public function __construct(private Uuid $uuid)
            {
            }

            public function generate(): Uuid
            {
                return $this->uuid;
            }
        };
    }
}
