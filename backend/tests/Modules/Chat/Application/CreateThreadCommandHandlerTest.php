<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Chat\Application\CreateThreadCommand;
use Modules\Chat\Application\CreateThreadCommandHandler;
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

final class CreateThreadCommandHandlerTest extends TestCase
{
    private const string NOW = '2026-11-16T12:00:00+00:00';

    public function testShouldCreateThreadWhenSessionExistsAndActorIsOwner(): void
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

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $thread = $sut(new CreateThreadCommand($actorId, $session->id(), 'Onboarding'));

        self::assertSame(1, $threads->count());
        self::assertTrue($thread->sessionId()->equals($session->id()));
        self::assertSame('Onboarding', $thread->subject());
        self::assertNull($thread->parentThreadId());
        self::assertEquals(new DateTimeImmutable(self::NOW), $thread->createdAt());
    }

    public function testShouldCreateThreadWithParentWhenParentBelongsToSession(): void
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
        $threads->save($parent);

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $thread = $sut(new CreateThreadCommand($actorId, $session->id(), 'Child', $parent->id()));

        self::assertSame(2, $threads->count());
        self::assertNotNull($thread->parentThreadId());
        self::assertTrue($thread->parentThreadId()->equals($parent->id()));
    }

    public function testShouldThrowNotFoundWhenSessionDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $unknownSessionId = ChatSessionId::generate();

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new CreateThreadCommand($actorId, $unknownSessionId, 'Orphan'));
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

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(AccessDeniedException::class);

        $sut(new CreateThreadCommand($foreignActorId, $session->id(), 'Trespass'));
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
        $unknownParentId = ChatThreadId::generate();

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(NotFoundHttpException::class);

        $sut(new CreateThreadCommand($actorId, $session->id(), 'Child', $unknownParentId));
    }

    public function testShouldThrowBadRequestWhenParentThreadBelongsToDifferentSession(): void
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

        $foreignParent = ChatThread::create(
            ChatThreadId::generate(),
            $otherSession->id(),
            'Foreign parent',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $threads->save($foreignParent);

        $sut = new CreateThreadCommandHandler($sessions, $threads, new FrozenClock(new DateTimeImmutable(self::NOW)));

        $this->expectException(BadRequestHttpException::class);

        $sut(new CreateThreadCommand($actorId, $targetSession->id(), 'Child', $foreignParent->id()));
    }
}
