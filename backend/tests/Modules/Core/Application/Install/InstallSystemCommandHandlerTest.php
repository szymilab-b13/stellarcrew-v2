<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Application\Install;

use function count;

use DateTimeImmutable;
use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\InvalidInstallInputException;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Application\Install\PasswordHasher;
use Modules\Core\Application\Install\SystemAlreadyInstalledException;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Core\Domain\User;
use Modules\Core\Domain\UserRepository;
use Modules\Core\Domain\UserRole;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UuidGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class InstallSystemCommandHandlerTest extends TestCase
{
    public function testShouldPersistAdminWithHashedPasswordWhenNotInstalled(): void
    {
        $users = $this->userRepository();
        $settings = $this->settingRepository([]);
        $sut = new InstallSystemCommandHandler(
            $users,
            $settings,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::fromString('0192f3f5-0000-7000-8000-000000000001')),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $sut(new InstallSystemCommand('admin@example.com', 'secret-pass-long-enough'));

        $admin = $users->findByEmail('admin@example.com');
        self::assertNotNull($admin);
        self::assertSame('admin@example.com', $admin->email());
        self::assertSame('hashed:secret-pass-long-enough', $admin->passwordHash());
        self::assertSame(UserRole::Admin, $admin->role());
    }

    public function testShouldFlipIsInstalledFlagWhenAdminCreated(): void
    {
        $settings = $this->settingRepository([]);
        $sut = new InstallSystemCommandHandler(
            $this->userRepository(),
            $settings,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::v7()),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $sut(new InstallSystemCommand('admin@example.com', 'secret-pass-long-enough'));

        $flag = $settings->get(IsInstalledQueryHandler::FLAG_NAME);
        self::assertNotNull($flag);
        self::assertSame(IsInstalledQueryHandler::TRUE_VALUE, $flag->value());
    }

    public function testShouldThrowWhenSystemAlreadyInstalled(): void
    {
        $settings = $this->settingRepository([
            IsInstalledQueryHandler::FLAG_NAME => new SystemSetting(
                IsInstalledQueryHandler::FLAG_NAME,
                IsInstalledQueryHandler::TRUE_VALUE,
                new DateTimeImmutable('2026-11-04T09:00:00+00:00'),
            ),
        ]);
        $sut = new InstallSystemCommandHandler(
            $this->userRepository(),
            $settings,
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::v7()),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(SystemAlreadyInstalledException::class);

        $sut(new InstallSystemCommand('admin@example.com', 'secret-pass-long-enough'));
    }

    public function testShouldThrowWhenEmailInvalid(): void
    {
        $sut = new InstallSystemCommandHandler(
            $this->userRepository(),
            $this->settingRepository([]),
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::v7()),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(InvalidInstallInputException::class);

        $sut(new InstallSystemCommand('not-an-email', 'secret-pass-long-enough'));
    }

    public function testShouldThrowWhenPasswordTooShort(): void
    {
        $sut = new InstallSystemCommandHandler(
            $this->userRepository(),
            $this->settingRepository([]),
            $this->passwordHasher(),
            $this->uuidGenerator(Uuid::v7()),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(InvalidInstallInputException::class);

        $sut(new InstallSystemCommand('admin@example.com', 'short'));
    }

    /**
     * @param array<string, SystemSetting> $settings
     */
    private function settingRepository(array $settings): SystemSettingRepository
    {
        return new class ($settings) implements SystemSettingRepository {
            /** @param array<string, SystemSetting> $settings */
            public function __construct(private array $settings)
            {
            }

            public function get(string $name): ?SystemSetting
            {
                return $this->settings[$name] ?? null;
            }

            public function save(SystemSetting $setting): void
            {
                $this->settings[$setting->name()] = $setting;
            }
        };
    }

    private function userRepository(): UserRepository
    {
        return new class () implements UserRepository {
            /** @var array<string, User> */
            private array $byEmail = [];

            public function save(User $user): void
            {
                $this->byEmail[$user->email()] = $user;
            }

            public function findByEmail(string $email): ?User
            {
                return $this->byEmail[$email] ?? null;
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
