<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Application\Install;

use App\Tests\Fixtures\Auth\SpyAuthApi;
use DateTimeImmutable;
use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\InvalidInstallInputException;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Application\Install\SystemAlreadyInstalledException;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Shared\Kernel\FrozenClock;
use PHPUnit\Framework\TestCase;

final class InstallSystemCommandHandlerTest extends TestCase
{
    public function testShouldCreateAdminThroughAuthApiWhenNotInstalled(): void
    {
        $auth = new SpyAuthApi();
        $sut = new InstallSystemCommandHandler(
            $auth,
            $this->settingRepository([]),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $sut(new InstallSystemCommand('admin@example.com', 'secret-pass-long-enough'));

        self::assertCount(1, $auth->calls);
        self::assertSame(
            ['email' => 'admin@example.com', 'password' => 'secret-pass-long-enough', 'displayName' => null],
            $auth->calls[0],
        );
    }

    public function testShouldFlipIsInstalledFlagWhenAdminCreated(): void
    {
        $settings = $this->settingRepository([]);
        $sut = new InstallSystemCommandHandler(
            new SpyAuthApi(),
            $settings,
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
            new SpyAuthApi(),
            $settings,
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(SystemAlreadyInstalledException::class);

        $sut(new InstallSystemCommand('admin@example.com', 'secret-pass-long-enough'));
    }

    public function testShouldThrowWhenEmailInvalid(): void
    {
        $sut = new InstallSystemCommandHandler(
            new SpyAuthApi(),
            $this->settingRepository([]),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(InvalidInstallInputException::class);

        $sut(new InstallSystemCommand('not-an-email', 'secret-pass-long-enough'));
    }

    public function testShouldThrowWhenPasswordTooShort(): void
    {
        $sut = new InstallSystemCommandHandler(
            new SpyAuthApi(),
            $this->settingRepository([]),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(InvalidInstallInputException::class);

        $sut(new InstallSystemCommand('admin@example.com', 'short'));
    }

    public function testShouldPassDisplayNameAndPersistBrandingAndWorkspaceSettings(): void
    {
        $auth = new SpyAuthApi();
        $settings = $this->settingRepository([]);
        $sut = new InstallSystemCommandHandler(
            $auth,
            $settings,
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $sut(new InstallSystemCommand(
            adminEmail: 'admin@example.com',
            adminPlaintextPassword: 'secret-pass-long-enough',
            adminDisplayName: 'Tomek',
            instanceName: 'Acme',
            locale: 'pl',
            workspaceName: 'Acme HQ',
            selectedAgentSlugs: ['maja', 'viktor'],
            logoRelativePath: 'var/uploads/branding/logo.png',
        ));

        self::assertSame('Tomek', $auth->calls[0]['displayName']);
        self::assertSame('Acme', $settings->get(InstallSystemCommandHandler::KEY_INSTANCE_NAME)?->value());
        self::assertSame('pl', $settings->get(InstallSystemCommandHandler::KEY_LOCALE)?->value());
        self::assertSame('var/uploads/branding/logo.png', $settings->get(InstallSystemCommandHandler::KEY_LOGO_PATH)?->value());
        self::assertSame('Acme HQ', $settings->get(InstallSystemCommandHandler::KEY_WORKSPACE_NAME)?->value());
        self::assertSame('["maja","viktor"]', $settings->get(InstallSystemCommandHandler::KEY_WORKSPACE_AGENTS)?->value());
    }

    public function testShouldRejectUnsupportedLocale(): void
    {
        $sut = new InstallSystemCommandHandler(
            new SpyAuthApi(),
            $this->settingRepository([]),
            new FrozenClock(new DateTimeImmutable('2026-11-05T09:00:00+00:00')),
        );

        $this->expectException(InvalidInstallInputException::class);

        $sut(new InstallSystemCommand(
            adminEmail: 'admin@example.com',
            adminPlaintextPassword: 'secret-pass-long-enough',
            locale: 'fr',
        ));
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
