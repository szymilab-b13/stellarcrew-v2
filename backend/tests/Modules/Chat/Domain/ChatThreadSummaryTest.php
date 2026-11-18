<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Domain;

use DateTimeImmutable;
use Modules\Chat\Domain\ChatThreadSummary;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatThreadId;
use PHPUnit\Framework\TestCase;

final class ChatThreadSummaryTest extends TestCase
{
    public function testShouldCreateEmptySummaryWithOnlyThreadIdWhenCreated(): void
    {
        $threadId = ChatThreadId::generate();

        $sut = ChatThreadSummary::createEmpty($threadId);

        self::assertSame($threadId, $sut->threadId());
        self::assertNull($sut->shortDescription());
        self::assertNull($sut->summaryJson());
        self::assertNull($sut->messagesCoveredUntilId());
        self::assertNull($sut->lastGeneratedAt());
        self::assertNull($sut->generatorVersion());
    }

    public function testShouldUpdateAllFieldsAndStampLastGeneratedAtWhenGeneratorRuns(): void
    {
        $sut = ChatThreadSummary::createEmpty(ChatThreadId::generate());
        $coveredUntil = ChatMessageId::generate();
        $generatedAt = new DateTimeImmutable('2026-11-16T17:00:00+00:00');

        $sut->updateFromGenerator(
            'Rozmowa o deployu produkcyjnym',
            ['bullets' => ['Deploy jutro', 'Czekamy specyfikacji'], 'sentiment' => 'neutral'],
            $coveredUntil,
            'v1',
            $generatedAt,
        );

        self::assertSame('Rozmowa o deployu produkcyjnym', $sut->shortDescription());
        self::assertSame(
            ['bullets' => ['Deploy jutro', 'Czekamy specyfikacji'], 'sentiment' => 'neutral'],
            $sut->summaryJson(),
        );
        self::assertSame($coveredUntil, $sut->messagesCoveredUntilId());
        self::assertSame('v1', $sut->generatorVersion());
        self::assertEquals($generatedAt, $sut->lastGeneratedAt());
    }

    public function testShouldOverwritePreviousValuesWhenRegenerated(): void
    {
        $sut = ChatThreadSummary::createEmpty(ChatThreadId::generate());
        $firstCovered = ChatMessageId::generate();
        $sut->updateFromGenerator(
            'Pierwsza wersja',
            ['version' => 1],
            $firstCovered,
            'v1',
            new DateTimeImmutable('2026-11-16T17:00:00+00:00'),
        );

        $secondCovered = ChatMessageId::generate();
        $secondGeneratedAt = new DateTimeImmutable('2026-11-16T18:00:00+00:00');
        $sut->updateFromGenerator(
            'Druga wersja',
            ['version' => 2, 'extra' => 'field'],
            $secondCovered,
            'v2',
            $secondGeneratedAt,
        );

        self::assertSame('Druga wersja', $sut->shortDescription());
        self::assertSame(['version' => 2, 'extra' => 'field'], $sut->summaryJson());
        self::assertSame($secondCovered, $sut->messagesCoveredUntilId());
        self::assertSame('v2', $sut->generatorVersion());
        self::assertEquals($secondGeneratedAt, $sut->lastGeneratedAt());
    }

    public function testShouldAllowPartialPayloadWhenUpdated(): void
    {
        $sut = ChatThreadSummary::createEmpty(ChatThreadId::generate());
        $generatedAt = new DateTimeImmutable('2026-11-16T17:30:00+00:00');

        $sut->updateFromGenerator(
            'Tylko krotki opis',
            null,
            null,
            null,
            $generatedAt,
        );

        self::assertSame('Tylko krotki opis', $sut->shortDescription());
        self::assertNull($sut->summaryJson());
        self::assertNull($sut->messagesCoveredUntilId());
        self::assertNull($sut->generatorVersion());
        self::assertEquals($generatedAt, $sut->lastGeneratedAt());
    }
}
