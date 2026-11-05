<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

interface SystemSettingRepository
{
    public function get(string $name): ?SystemSetting;

    public function save(SystemSetting $setting): void;
}
