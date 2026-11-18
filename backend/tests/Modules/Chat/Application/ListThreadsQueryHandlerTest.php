<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Chat\Application\ListThreadsQuery;
use Modules\Chat\Application\ListThreadsQueryHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class ListThreadsQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyListWhenSessionHasNoThreads(): void
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

        $sut = new ListThreadsQueryHandler($sessions, $threads);

        $result = $sut(new ListThreadsQuery($actorId, $session->id()));

        self::assertSame([], $result);
    }

    public function testShouldReturnThreadsForSessionWhenActorIsOwner(): void
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
        $first = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Onboarding',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $second = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Billing',
            new DateTimeImmutable('2026-11-16T10:10:00+00:00'),
        );
        $threads->save($first);
        $threads->save($second);

        $sut = new ListThreadsQueryHandler($sessions, $threads);

        $result = $sut(new ListThreadsQuery($actorId, $session->id()));

        self::assertCount(2, $result);
        self::assertTrue($result[0]->id()->equals($first->id()));
        self::assertTrue($result[1]->id()->equals($second->id()));
    }

    public function testShouldIgnoreThreadsFromOtherSessions(): void
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

        $mine = ChatThread::create(
            ChatThreadId::generate(),
            $targetSession->id(),
            'Mine',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $foreign = ChatThread::create(
            ChatThreadId::generate(),
            $otherSession->id(),
            'Foreign',
            new DateTimeImmutable('2026-11-16T10:05:00+00:00'),
        );
        $threads->save($mine);
        $threads->save($foreign);

        $sut = new ListThreadsQueryHandler($sessions, $threads);

        $result = $sut(new ListThreadsQuery($actorId, $targetSession->id()));

        self::assertCount(1, $result);
        self::assertTrue($result[0]->id()->equals($mine->id()));
    }

    public function testShouldThrowNotFoundWhenSessionDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $unknownSessionId = ChatSessionId::generate();

        $sut = new ListThreadsQueryHandler($sessions, $threads);

        $this->expectException(NotFoundHttpException::class);

        $sut(new ListThreadsQuery($actorId, $unknownSessionId));
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

        $sut = new ListThreadsQueryHandler($sessions, $threads);

        $this->expectException(AccessDeniedException::class);

        $sut(new ListThreadsQuery($foreignActorId, $session->id()));
    }
}
