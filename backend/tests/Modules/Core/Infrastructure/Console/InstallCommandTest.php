<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Console;

use function count;

use DateTimeImmutable;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Application\Install\PasswordHasher;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Core\Domain\User;
use Modules\Core\Domain\UserRepository;
use Modules\Core\Infrastructure\Console\InstallCommand;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\UuidGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class InstallCommandTest extends TestCase
{
    public function testShouldCreateAdminAndMarkSystemInstalledWhenNotInstalled(): void
    {
        $users = $this->userRepository();
        $settings = $this->settingRepository([]);
        $tester = $this->tester($users, $settings);

        $tester->setInputs(['admin@example.com', 'secret-pass-long-enough', 'secret-pass-long-enough']);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Admin admin@example.com created', $tester->getDisplay());
        self::assertNotNull($users->findByEmail('admin@example.com'));
        $flag = $settings->get(IsInstalledQueryHandler::FLAG_NAME);
        self::assertNotNull($flag);
        self::assertSame(IsInstalledQueryHandler::TRUE_VALUE, $flag->value());
    }

    public function testShouldReturnFailureWhenSystemAlreadyInstalled(): void
    {
        $settings = $this->settingRepository([
            IsInstalledQueryHandler::FLAG_NAME => new SystemSetting(
                IsInstalledQueryHandler::FLAG_NAME,
                IsInstalledQueryHandler::TRUE_VALUE,
                new DateTimeImmutable('2026-11-04T09:00:00+00:00'),
            ),
        ]);
        $tester = $this->tester($this->userRepository(), $settings);

        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('already installed', $tester->getDisplay());
    }

    public function testShouldRejectWhenPasswordAndConfirmationDoNotMatch(): void
    {
        $tester = $this->tester($this->userRepository(), $this->settingRepository([]));

        $tester->setInputs(['admin@example.com', 'secret-pass-long-enough', 'different-pass-also-long']);

        $this->expectExceptionMessage('Passwords do not match.');

        $tester->execute([]);
    }

    public function testShouldRejectShortPassword(): void
    {
        $tester = $this->tester($this->userRepository(), $this->settingRepository([]));

        $tester->setInputs(['admin@example.com', 'short', 'short', 'short', 'short']);

        $this->expectExceptionMessageMatches('/at least 10 characters/');

        $tester->execute([]);
    }

    private function tester(UserRepository $users, SystemSettingRepository $settings): CommandTester
    {
        $isInstalled = new IsInstalledQueryHandler($settings);
        $install = new InstallSystemCommandHandler(
            $users,
            $settings,
            $this->passwordHasher(),
            $this->uuidGenerator(),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );
        $command = new InstallCommand($isInstalled, $install);
        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($application->find('stellarcrew:install'));
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

    private function passwordHasher(): PasswordHasher
    {
        return new class () implements PasswordHasher {
            public function hash(string $plaintext): string
            {
                return 'hashed:' . $plaintext;
            }
        };
    }

    private function uuidGenerator(): UuidGenerator
    {
        return new class () implements UuidGenerator {
            public function generate(): UuidV7
            {
                return Uuid::v7();
            }
        };
    }
}
