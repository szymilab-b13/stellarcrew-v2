<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Infrastructure\Console;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use DateTimeImmutable;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Core\Infrastructure\Console\InstallCommand;
use Modules\Shared\Kernel\FrozenClock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class InstallCommandTest extends TestCase
{
    public function testShouldCreateAdminAndMarkSystemInstalledWhenNotInstalled(): void
    {
        $auth = new SpyAuthApi();
        $settings = $this->settingRepository([]);
        $tester = $this->tester($auth, $settings);

        $tester->setInputs([
            'admin@example.com',
            'secret-pass-long-enough',
            'secret-pass-long-enough',
            'Tomek',
        ]);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Admin admin@example.com created', $tester->getDisplay());
        self::assertCount(1, $auth->calls);
        self::assertSame('admin@example.com', $auth->calls[0]['email']);
        self::assertSame('Tomek', $auth->calls[0]['displayName']);
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
        $tester = $this->tester(new SpyAuthApi(), $settings);

        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('already installed', $tester->getDisplay());
    }

    public function testShouldRejectWhenPasswordAndConfirmationDoNotMatch(): void
    {
        $tester = $this->tester(new SpyAuthApi(), $this->settingRepository([]));

        $tester->setInputs(['admin@example.com', 'secret-pass-long-enough', 'different-pass-also-long']);

        $this->expectExceptionMessage('Passwords do not match.');

        $tester->execute([]);
    }

    public function testShouldRejectShortPassword(): void
    {
        $tester = $this->tester(new SpyAuthApi(), $this->settingRepository([]));

        $tester->setInputs(['admin@example.com', 'short', 'short', 'short', 'short']);

        $this->expectExceptionMessageMatches('/at least 10 characters/');

        $tester->execute([]);
    }

    private function tester(SpyAuthApi $auth, SystemSettingRepository $settings): CommandTester
    {
        $isInstalled = new IsInstalledQueryHandler($settings);
        $install = new InstallSystemCommandHandler(
            $auth,
            $settings,
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );
        $command = new InstallCommand($isInstalled, $install);
        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($application->find('stellarcrew:install'));
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

}
