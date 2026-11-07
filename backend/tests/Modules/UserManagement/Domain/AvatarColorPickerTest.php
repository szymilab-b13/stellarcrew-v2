<?php

declare(strict_types=1);

namespace App\Tests\Modules\UserManagement\Domain;

use function count;
use function in_array;

use Modules\UserManagement\Domain\AvatarColorPicker;
use PHPUnit\Framework\TestCase;

final class AvatarColorPickerTest extends TestCase
{
    public function testShouldReturnSameColorWhenCalledWithSameEmail(): void
    {
        $first = AvatarColorPicker::pick('tomek@example.com');
        $second = AvatarColorPicker::pick('tomek@example.com');

        self::assertSame($first, $second);
    }

    public function testShouldReturnColorFromPaletteWhenCalled(): void
    {
        $color = AvatarColorPicker::pick('any-email@example.com');

        self::assertTrue(in_array($color, AvatarColorPicker::PALETTE, true));
    }

    public function testShouldIgnoreCaseWhenHashingEmail(): void
    {
        $lower = AvatarColorPicker::pick('tomek@example.com');
        $mixed = AvatarColorPicker::pick('Tomek@Example.com');

        self::assertSame($lower, $mixed);
    }

    public function testShouldIgnoreSurroundingWhitespaceWhenHashingEmail(): void
    {
        $clean = AvatarColorPicker::pick('tomek@example.com');
        $padded = AvatarColorPicker::pick('  tomek@example.com  ');

        self::assertSame($clean, $padded);
    }

    public function testShouldDistributeAcrossPaletteWhenHashingManyEmails(): void
    {
        $seen = [];
        foreach (range(1, 32) as $index) {
            $seen[AvatarColorPicker::pick("user{$index}@example.com")] = true;
        }

        self::assertGreaterThan(1, count($seen));
    }
}
