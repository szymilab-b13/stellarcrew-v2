<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use DateTimeImmutable;
use Modules\Chat\Application\ListMessagesQuery;
use Modules\Chat\Application\ListMessagesQueryHandler;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class ListMessagesQueryHandlerTest extends TestCase
{
    public function testShouldReturnEmptyResultWhenThreadHasNoMessages(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId);
        $sessions->save($session);
        $thread = $this->makeThread($session->id());
        $threads->save($thread);

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $result = $sut(new ListMessagesQuery($actorId, $thread->id(), 50, 0));

        self::assertSame([], $result->messages);
        self::assertSame(0, $result->total);
        self::assertSame(50, $result->limit);
        self::assertSame(0, $result->offset);
    }

    public function testShouldReturnMessagesOrderedByCreatedAtAscendingWhenActorIsOwner(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId);
        $sessions->save($session);
        $thread = $this->makeThread($session->id());
        $threads->save($thread);
        $first = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $third = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:10:00+00:00'));
        $second = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:05:00+00:00'));
        $messages->save($third);
        $messages->save($first);
        $messages->save($second);

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $result = $sut(new ListMessagesQuery($actorId, $thread->id(), 50, 0));

        self::assertCount(3, $result->messages);
        self::assertSame(3, $result->total);
        self::assertTrue($result->messages[0]->id()->equals($first->id()));
        self::assertTrue($result->messages[1]->id()->equals($second->id()));
        self::assertTrue($result->messages[2]->id()->equals($third->id()));
    }

    public function testShouldRespectLimitAndOffsetWhileReportingFullTotal(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId);
        $sessions->save($session);
        $thread = $this->makeThread($session->id());
        $threads->save($thread);
        $one = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $two = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:01:00+00:00'));
        $three = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:02:00+00:00'));
        $four = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:03:00+00:00'));
        $five = $this->makeMessage($session->id(), $thread->id(), new DateTimeImmutable('2026-11-16T10:04:00+00:00'));
        $messages->save($one);
        $messages->save($two);
        $messages->save($three);
        $messages->save($four);
        $messages->save($five);

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $result = $sut(new ListMessagesQuery($actorId, $thread->id(), 2, 1));

        self::assertCount(2, $result->messages);
        self::assertSame(5, $result->total);
        self::assertSame(2, $result->limit);
        self::assertSame(1, $result->offset);
        self::assertTrue($result->messages[0]->id()->equals($two->id()));
        self::assertTrue($result->messages[1]->id()->equals($three->id()));
    }

    public function testShouldIgnoreMessagesFromOtherThreads(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $actorId = new UserId(Uuid::v7());
        $session = $this->makeSession($actorId);
        $sessions->save($session);
        $targetThread = $this->makeThread($session->id());
        $otherThread = $this->makeThread($session->id());
        $threads->save($targetThread);
        $threads->save($otherThread);

        $mine = $this->makeMessage($session->id(), $targetThread->id(), new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $foreign = $this->makeMessage($session->id(), $otherThread->id(), new DateTimeImmutable('2026-11-16T10:00:00+00:00'));
        $messages->save($mine);
        $messages->save($foreign);

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $result = $sut(new ListMessagesQuery($actorId, $targetThread->id(), 50, 0));

        self::assertCount(1, $result->messages);
        self::assertSame(1, $result->total);
        self::assertTrue($result->messages[0]->id()->equals($mine->id()));
    }

    public function testShouldThrowNotFoundWhenThreadDoesNotExist(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $actorId = new UserId(Uuid::v7());
        $unknownThreadId = ChatThreadId::generate();

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $this->expectException(NotFoundHttpException::class);

        $sut(new ListMessagesQuery($actorId, $unknownThreadId, 50, 0));
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotSessionOwner(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $ownerId = new UserId(Uuid::v7());
        $foreignActorId = new UserId(Uuid::v7());
        $session = $this->makeSession($ownerId);
        $sessions->save($session);
        $thread = $this->makeThread($session->id());
        $threads->save($thread);

        $sut = new ListMessagesQueryHandler($threads, $sessions, $messages);

        $this->expectException(AccessDeniedException::class);

        $sut(new ListMessagesQuery($foreignActorId, $thread->id(), 50, 0));
    }

    private function makeSession(UserId $ownerUserId): ChatSession
    {
        return ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $ownerUserId,
            'Chat',
            new DateTimeImmutable('2026-11-16T09:00:00+00:00'),
        );
    }

    private function makeThread(ChatSessionId $sessionId): ChatThread
    {
        return ChatThread::create(
            ChatThreadId::generate(),
            $sessionId,
            'Onboarding',
            new DateTimeImmutable('2026-11-16T09:30:00+00:00'),
        );
    }

    private function makeMessage(ChatSessionId $sessionId, ChatThreadId $threadId, DateTimeImmutable $createdAt): ChatMessage
    {
        return ChatMessage::send(
            ChatMessageId::generate(),
            $sessionId,
            $threadId,
            ParticipantType::User,
            Uuid::v7(),
            ParticipantType::Agent,
            Uuid::v7(),
            'hello',
            Priority::P2,
            'content',
            ContentType::Text,
            [],
            null,
            Uuid::v7(),
            EngineType::CliTerminal,
            ClassifiedBy::Sender,
            null,
            $createdAt,
        );
    }
}
