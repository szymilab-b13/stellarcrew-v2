<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;

final readonly class IsInstalledQueryHandler
{
    public const string FLAG_NAME = 'is_installed';

    public const string TRUE_VALUE = 'true';

    public function __construct(private SystemSettingRepository $settings)
    {
    }

    public function __invoke(): bool
    {
        $setting = $this->settings->get(self::FLAG_NAME);
        return $setting instanceof SystemSetting && $setting->value() === self::TRUE_VALUE;
    }
}
