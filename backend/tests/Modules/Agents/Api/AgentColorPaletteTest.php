<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Api;

use function count;
use function in_array;

use Modules\Agents\Api\AgentColorPalette;
use PHPUnit\Framework\TestCase;

use function preg_match;

final class AgentColorPaletteTest extends TestCase
{
    public function testShouldReturnTheSameColorForTheSameSlug(): void
    {
        self::assertSame(
            AgentColorPalette::deriveFromSlug('maja'),
            AgentColorPalette::deriveFromSlug('maja'),
        );
    }

    public function testShouldReturnHexColorFromPredefinedPalette(): void
    {
        $color = AgentColorPalette::deriveFromSlug('hefajstos');

        self::assertSame(1, preg_match('/^#[0-9a-f]{6}$/', $color));
        self::assertTrue(in_array($color, AgentColorPalette::palette(), true));
    }

    public function testShouldPickDifferentColorsForDifferentSlugs(): void
    {
        $colors = [
            AgentColorPalette::deriveFromSlug('maja'),
            AgentColorPalette::deriveFromSlug('hefajstos'),
            AgentColorPalette::deriveFromSlug('viktor'),
            AgentColorPalette::deriveFromSlug('luna'),
            AgentColorPalette::deriveFromSlug('nessor'),
        ];

        self::assertGreaterThanOrEqual(3, count(array_unique($colors)));
    }

    public function testShouldExposeNonEmptyPalette(): void
    {
        $palette = AgentColorPalette::palette();

        self::assertNotEmpty($palette);
        foreach ($palette as $color) {
            self::assertSame(1, preg_match('/^#[0-9a-f]{6}$/', $color));
        }
    }
}
