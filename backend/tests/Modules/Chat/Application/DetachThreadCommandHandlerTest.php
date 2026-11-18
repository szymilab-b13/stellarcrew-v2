<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Chat\Application\DetachThreadCommand;
use Modules\Chat\Application\DetachThreadCommandHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class DetachThreadCommandHandlerTest extends TestCase
{
    private const string NOW = '2026-11-16T12:00:00+00:00';

    public function testShouldDetachThreadFromParentWhenActorIsOwner(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Chat',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $sessions->save($session);
        $parent = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Parent',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Child',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $thread->attachToParent($parent->id(), new DateTimeImmutable('2026-11-16T10:15:00+00:00'));

        $threads->save($parent);
        $threads->save($thread);

        $sut = new DetachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $result = $sut(new DetachThreadCommand($actorId, $thread->id()));

        self::assertNull($result->parentThreadId());
        self::assertEquals(new DateTimeImmutable(self::NOW), $result->updatedAt());
    }

    public function testShouldBeNoOpWhenThreadAlreadyHasNoParent(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Chat',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $sessions->save($session);
        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Root',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $threads->save($thread);

        $sut = new DetachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $result = $sut(new DetachThreadCommand($actorId, $thread->id()));

        self::assertNull($result->parentThreadId());
        self::assertEquals(new DateTimeImmutable(self::NOW), $result->updatedAt());
    }

    public function testShouldThrowNotFoundWhenThreadDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $unknownThreadId = ChatThreadId::generate();

        $sut = new DetachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new DetachThreadCommand($actorId, $unknownThreadId));
    }

    public function testShouldThrowNotFoundWhenSessionDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $orphanThread = ChatThread::create(
            ChatThreadId::generate(),
            ChatSessionId::generate(),
            'Orphan',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $threads->save($orphanThread);

        $sut = new DetachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new DetachThreadCommand($actorId, $orphanThread->id()));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotSessionOwner(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $ownerId = new UserId(Uuid::v7());
        $foreignActorId = new UserId(Uuid::v7());
        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $ownerId,
            'Chat',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $sessions->save($session);
        $parent = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Parent',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Child',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $thread->attachToParent($parent->id(), new DateTimeImmutable('2026-11-16T10:15:00+00:00'));

        $threads->save($parent);
        $threads->save($thread);

        $sut = new DetachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(AccessDeniedException::class);

        $sut(new DetachThreadCommand($foreignActorId, $thread->id()));
    }
}
