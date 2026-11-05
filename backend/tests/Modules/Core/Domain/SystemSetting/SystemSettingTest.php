<?php

declare(strict_types=1);

namespace App\Tests\Modules\Core\Domain\SystemSetting;

use DateTimeImmutable;
use Modules\Core\Domain\SystemSetting;
use PHPUnit\Framework\TestCase;

final class SystemSettingTest extends TestCase
{
    public function testShouldExposeNameValueAndUpdatedAtWhenCreated(): void
    {
        $updatedAt = new DateTimeImmutable('2026-11-05T09:00:00+00:00');

        $sut = new SystemSetting('is_installed', 'true', $updatedAt);

        self::assertSame('is_installed', $sut->name());
        self::assertSame('true', $sut->value());
        self::assertEquals($updatedAt, $sut->updatedAt());
    }

    public function testShouldAllowNullValueWhenNoValueStored(): void
    {
        $sut = new SystemSetting('maintenance_message', null, new DateTimeImmutable('2026-11-05T09:00:00+00:00'));

        self::assertNull($sut->value());
    }

    public function testShouldUpdateValueAndTouchUpdatedAtWhenValueChanges(): void
    {
        $originalUpdatedAt = new DateTimeImmutable('2026-11-05T09:00:00+00:00');
        $sut = new SystemSetting('is_installed', 'false', $originalUpdatedAt);
        $newUpdatedAt = new DateTimeImmutable('2026-11-05T10:00:00+00:00');

        $sut->changeValue('true', $newUpdatedAt);

        self::assertSame('true', $sut->value());
        self::assertEquals($newUpdatedAt, $sut->updatedAt());
    }
}
