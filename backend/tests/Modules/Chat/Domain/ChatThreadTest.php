<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\Chat\Domain\ChatThread;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use PHPUnit\Framework\TestCase;

use function str_repeat;

final class ChatThreadTest extends TestCase
{
    public function testShouldExposeFieldsWhenCreated(): void
    {
        $id = ChatThreadId::generate();
        $sessionId = ChatSessionId::generate();
        $now = new DateTimeImmutable('2026-11-16T10:00:00+00:00');

        $sut = ChatThread::create($id, $sessionId, 'Onboarding questions', $now);

        self::assertSame($id, $sut->id());
        self::assertSame($sessionId, $sut->sessionId());
        self::assertSame('Onboarding questions', $sut->subject());
        self::assertEquals($now, $sut->createdAt());
        self::assertEquals($now, $sut->updatedAt());
        self::assertNull($sut->parentThreadId());
    }

    public function testShouldRejectBlankSubjectWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            '   ',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
    }

    public function testShouldRejectTooLongSubjectWhenCreated(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            str_repeat('a', 121),
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
    }

    public function testShouldAcceptSubjectAtMaxLengthWhenCreated(): void
    {
        $subject = str_repeat('a', 120);

        $sut = ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            $subject,
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );

        self::assertSame($subject, $sut->subject());
    }

    public function testShouldRenameAndStampUpdatedAtWhenRenamed(): void
    {
        $sut = $this->makeThread();
        $later = new DateTimeImmutable('2026-11-16T11:00:00+00:00');

        $sut->rename('Renamed thread', $later);

        self::assertSame('Renamed thread', $sut->subject());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldRejectBlankSubjectWhenRenamed(): void
    {
        $sut = $this->makeThread();

        $this->expectException(InvalidArgumentException::class);

        $sut->rename('   ', new DateTimeImmutable('2026-11-16T11:00:00+00:00'));
    }

    public function testShouldRejectTooLongSubjectWhenRenamed(): void
    {
        $sut = $this->makeThread();

        $this->expectException(InvalidArgumentException::class);

        $sut->rename(str_repeat('a', 121), new DateTimeImmutable('2026-11-16T11:00:00+00:00'));
    }

    public function testShouldAcceptInitialParentThreadIdWhenCreated(): void
    {
        $parent = ChatThreadId::generate();
        $sut = ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            'Child thread',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
            $parent,
        );

        self::assertSame($parent, $sut->parentThreadId());
    }

    public function testShouldAttachToParentAndStampUpdatedAtWhenAttached(): void
    {
        $sut = $this->makeThread();
        $parent = ChatThreadId::generate();
        $later = new DateTimeImmutable('2026-11-16T12:00:00+00:00');

        $sut->attachToParent($parent, $later);

        self::assertSame($parent, $sut->parentThreadId());
        self::assertEquals($later, $sut->updatedAt());
    }

    public function testShouldRejectAttachingThreadToItself(): void
    {
        $id = ChatThreadId::generate();
        $sut = ChatThread::create(
            $id,
            ChatSessionId::generate(),
            'Self parent attempt',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );

        $this->expectException(InvalidArgumentException::class);

        $sut->attachToParent($id, new DateTimeImmutable('2026-11-16T12:00:00+00:00'));
    }

    public function testShouldDetachFromParentAndStampUpdatedAtWhenDetached(): void
    {
        $parent = ChatThreadId::generate();
        $sut = ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            'Attached child',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
            $parent,
        );
        $later = new DateTimeImmutable('2026-11-16T13:00:00+00:00');

        $sut->detachFromParent($later);

        self::assertNull($sut->parentThreadId());
        self::assertEquals($later, $sut->updatedAt());
    }

    private function makeThread(): ChatThread
    {
        return ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            'Onboarding questions',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
    }
}
