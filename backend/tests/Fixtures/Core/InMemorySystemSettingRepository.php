<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use DateTimeImmutable;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;

final class InMemorySystemSettingRepository implements SystemSettingRepository
{
    /** @var array<string, SystemSetting> */
    private array $settings = [];

    public function get(string $name): ?SystemSetting
    {
        return $this->settings[$name] ?? null;
    }

    public function save(SystemSetting $setting): void
    {
        $this->settings[$setting->name()] = $setting;
    }

    public function markInstalled(DateTimeImmutable $at = new DateTimeImmutable('2026-11-05T09:00:00+00:00')): void
    {
        $this->save(new SystemSetting(
            IsInstalledQueryHandler::FLAG_NAME,
            IsInstalledQueryHandler::TRUE_VALUE,
            $at,
        ));
    }
}
