<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Chat\Domain\ChatSession;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class ChatSessionTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = ChatSessionId::generate();
        $organizationId = OrganizationId::generate();
        $ownerUserId = UserId::generate();
        $now = new DateTimeImmutable('2026-11-15T17:00:00+00:00');

        $sut = ChatSession::create($id, $organizationId, $ownerUserId, 'Session 2026-11-15', $now);

        self::assertSame($id, $sut->id());
        self::assertSame($organizationId, $sut->organizationId());
        self::assertSame($ownerUserId, $sut->ownerUserId());
        self::assertSame('Session 2026-11-15', $sut->label());
        self::assertEquals($now, $sut->startedAt());
        self::assertNull($sut->endedAt());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertFalse($sut->isEnded());
    }

    public function testShouldRejectBlankLabelWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            UserId::generate(),
            '   ',
            new DateTimeImmutable('2026-11-15T17:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongLabelWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            UserId::generate(),
            str_repeat('a', 121),
            new DateTimeImmutable('2026-11-15T17:00:00+00:00'),
        );
    }

    public function testShouldAcceptLabelAtMaxLengthWhenCreated(): void
    {
        $label = str_repeat('a', 120);

        $sut = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            UserId::generate(),
            $label,
            new DateTimeImmutable('2026-11-15T17:00:00+00:00'),
        );

        self::assertSame($label, $sut->label());
    }

    public function testShouldRenameAndStampUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeSession();
        $later = new DateTimeImmutable('2026-11-15T18:00:00+00:00');

        $sut->rename('Renamed session', $later);

        self::assertSame('Renamed session', $sut->label());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldRejectBlankLabelWhenRenamed(): void
    {
        $sut = $this->makeSession();

        $this->expectException(InvalidArgumentException::class);

        $sut->rename('   ', new DateTimeImmutable('2026-11-15T18:00:00+00:00'));
    }

    public function testShouldRejectTooLongLabelWhenRenamed(): void
    {
        $sut = $this->makeSession();

        $this->expectException(InvalidArgumentException::class);

        $sut->rename(str_repeat('a', 121), new DateTimeImmutable('2026-11-15T18:00:00+00:00'));
    }

    public function testShouldEndSessionAndStampUpdatedAtWhenEnded(): void
    {
        $sut = $this->makeSession();
        $endAt = new DateTimeImmutable('2026-11-15T19:30:00+00:00');

        $sut->end($endAt);

        self::assertTrue($sut->isEnded());
        self::assertEquals($endAt, $sut->endedAt());
        self::assertEquals($endAt, $sut->updatedAt());
    }

    public function testShouldBeIdempotentWhenEndedTwice(): void
    {
        $sut = $this->makeSession();
        $firstEnd = new DateTimeImmutable('2026-11-15T19:30:00+00:00');
        $secondEnd = new DateTimeImmutable('2026-11-15T20:00:00+00:00');

        $sut->end($firstEnd);
        $sut->end($secondEnd);

        self::assertEquals($firstEnd, $sut->endedAt());
        self::assertEquals($firstEnd, $sut->updatedAt());
    }

    private function makeSession(): ChatSession
    {
        return ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            UserId::generate(),
            'Session 2026-11-15',
            new DateTimeImmutable('2026-11-15T17:00:00+00:00'),
        );
    }
}
