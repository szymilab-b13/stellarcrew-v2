<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Chat\Application\AttachThreadCommand;
use Modules\Chat\Application\AttachThreadCommandHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class AttachThreadCommandHandlerTest extends TestCase
{
    private const string NOW = '2026-11-16T12:00:00+00:00';

    public function testShouldAttachThreadToParentWhenParentBelongsToSameSessionAndActorIsOwner(): void
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
        $threads->save($parent);
        $threads->save($thread);

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $result = $sut(new AttachThreadCommand($actorId, $thread->id(), $parent->id()));

        self::assertNotNull($result->parentThreadId());
        self::assertTrue($result->parentThreadId()->equals($parent->id()));
        self::assertEquals(new DateTimeImmutable(self::NOW), $result->updatedAt());
    }

    public function testShouldThrowBadRequestWhenAttachingThreadToItself(): void
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
            'Self',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $threads->save($thread);

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(BadRequestHttpException::class);

        $sut(new AttachThreadCommand($actorId, $thread->id(), $thread->id()));
    }

    public function testShouldThrowBadRequestWhenParentBelongsToDifferentSession(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $targetSession = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Target',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $otherSession = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Other',
            new DateTimeImmutable('2026-11-16T10:00:00+00:00'),
        );
        $sessions->save($targetSession);
        $sessions->save($otherSession);

        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $targetSession->id(),
            'Child',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $foreignParent = ChatThread::create(
            ChatThreadId::generate(),
            $otherSession->id(),
            'Foreign parent',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $threads->save($thread);
        $threads->save($foreignParent);

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(BadRequestHttpException::class);

        $sut(new AttachThreadCommand($actorId, $thread->id(), $foreignParent->id()));
    }

    public function testShouldThrowNotFoundWhenThreadDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $unknownThreadId = ChatThreadId::generate();
        $parentId = ChatThreadId::generate();

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new AttachThreadCommand($actorId, $unknownThreadId, $parentId));
    }

    public function testShouldThrowNotFoundWhenParentThreadDoesNotExist(): void
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
            'Child',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $threads->save($thread);
        $unknownParentId = ChatThreadId::generate();

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new AttachThreadCommand($actorId, $thread->id(), $unknownParentId));
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
        $threads->save($parent);
        $threads->save($thread);

        $sut = new AttachThreadCommandHandler($threads, $sessions, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(AccessDeniedException::class);

        $sut(new AttachThreadCommand($foreignActorId, $thread->id(), $parent->id()));
    }
}
