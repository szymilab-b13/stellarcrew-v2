<?php

declare(strict_types=1);

namespace App\Tests\Modules\Agents\Domain;

use Modules\Agents\Domain\AgentSlugGenerator;
use PHPUnit\Framework\TestCase;

use function strlen;

final class AgentSlugGeneratorTest extends TestCase
{
    public function testShouldLowercaseAndJoinWithHyphensWhenNameGiven(): void
    {
        self::assertSame('maja-project-manager', AgentSlugGenerator::fromName('Maja Project Manager'));
    }

    public function testShouldCollapseNonAlphanumericRunsWhenNameMessy(): void
    {
        self::assertSame('viktor-backend-dev', AgentSlugGenerator::fromName('Viktor    Backend / Dev!!!'));
    }

    public function testShouldTrimHyphensAtEdgesWhenNameBoundedByNonAlphanumeric(): void
    {
        self::assertSame('hefajstos', AgentSlugGenerator::fromName('  --Hefajstos--  '));
    }

    public function testShouldFallBackToGeneratedSlugWhenNameIsBlank(): void
    {
        $slug = AgentSlugGenerator::fromName('   ');

        self::assertMatchesRegularExpression('/^agent-\d{4}$/', $slug);
    }

    public function testShouldAppendRandomSuffixWhenAskedForFallback(): void
    {
        $slug = AgentSlugGenerator::withRandomSuffix('maja');

        self::assertMatchesRegularExpression('/^maja-\d{4}$/', $slug);
    }

    public function testShouldNotExceedMaxLengthWhenBaseIsAlreadyFull(): void
    {
        $base = str_repeat('a', 80);
        $slug = AgentSlugGenerator::withRandomSuffix($base);

        self::assertLessThanOrEqual(80, strlen($slug));
        self::assertMatchesRegularExpression('/-\d{4}$/', $slug);
    }
}
