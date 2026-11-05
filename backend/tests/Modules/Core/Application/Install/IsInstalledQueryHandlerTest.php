<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Application\Install;

use DateTimeImmutable;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use PHPUnit\Framework\TestCase;

final class IsInstalledQueryHandlerTest extends TestCase
{
    public function testShouldReturnFalseWhenFlagMissing(): void
    {
        $sut = new IsInstalledQueryHandler($this->repository([]));

        $result = $sut();

        self::assertFalse($result);
    }

    public function testShouldReturnFalseWhenFlagStoredAsFalse(): void
    {
        $setting = new SystemSetting('is_installed', 'false', new DateTimeImmutable('2026-11-05T09:00:00+00:00'));
        $sut = new IsInstalledQueryHandler($this->repository(['is_installed' => $setting]));

        $result = $sut();

        self::assertFalse($result);
    }

    public function testShouldReturnTrueWhenFlagStoredAsTrue(): void
    {
        $setting = new SystemSetting('is_installed', 'true', new DateTimeImmutable('2026-11-05T09:00:00+00:00'));
        $sut = new IsInstalledQueryHandler($this->repository(['is_installed' => $setting]));

        $result = $sut();

        self::assertTrue($result);
    }

    /**
     * @param array<string, SystemSetting> $settings
     */
    private function repository(array $settings): SystemSettingRepository
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
