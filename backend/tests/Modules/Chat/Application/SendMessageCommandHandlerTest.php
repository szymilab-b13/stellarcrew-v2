<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use DateTimeImmutable;
use Modules\Chat\Application\SendMessageCommand;
use Modules\Chat\Application\SendMessageCommandHandler;
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
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class SendMessageCommandHandlerTest extends TestCase
{
    private const string NOW = '2026-11-16T13:00:00+00:00';

    public function testShouldPersistMessageAndEmitAuditWhenSessionOwnedByActor(): void
    {
        $actorId = new UserId(Uuid::v7());
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $audit = new SpyAuditLogger();
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            $messages,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Session',
            new DateTimeImmutable(self::NOW),
        );
        $sessions->save($session);

        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'First topic',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($thread);

        $command = $this->command($actorId, $session->id(), $thread->id());

        $message = $sut($command);

        self::assertSame(1, $messages->count());
        self::assertTrue($message->sessionId()->equals($session->id()));
        self::assertTrue($message->threadId()->equals($thread->id()));
        self::assertSame('Hello', $message->subject());
        self::assertSame('Hello body', $message->content());
        self::assertCount(1, $audit->entries);
        self::assertSame(SendMessageCommandHandler::AUDIT_ACTION, $audit->entries[0]->action);
        self::assertSame(SendMessageCommandHandler::AUDIT_RESOURCE_TYPE, $audit->entries[0]->resourceType);
        self::assertSame((string) $message->id(), $audit->entries[0]->resourceId);
        self::assertNotNull($audit->entries[0]->userId);
        self::assertTrue($audit->entries[0]->userId->equals($actorId));
    }

    public function testShouldThrowNotFoundWhenSessionMissing(): void
    {
        $sut = $this->buildHandler();
        $command = $this->command(new UserId(Uuid::v7()), ChatSessionId::generate(), ChatThreadId::generate());

        $this->expectException(NotFoundHttpException::class);

        $sut($command);
    }

    public function testShouldThrowAccessDeniedWhenActorIsNotSessionOwner(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $owner = new UserId(Uuid::v7());
        $intruder = new UserId(Uuid::v7());
        $sut = $this->buildHandler($sessions, $threads);

        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $owner,
            'Session',
            new DateTimeImmutable(self::NOW),
        );
        $sessions->save($session);

        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Topic',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($thread);

        $command = $this->command($intruder, $session->id(), $thread->id());

        $this->expectException(AccessDeniedException::class);

        $sut($command);
    }

    public function testShouldThrowNotFoundWhenThreadMissing(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $actorId = new UserId(Uuid::v7());
        $sut = $this->buildHandler($sessions);

        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Session',
            new DateTimeImmutable(self::NOW),
        );
        $sessions->save($session);

        $command = $this->command($actorId, $session->id(), ChatThreadId::generate());

        $this->expectException(NotFoundHttpException::class);

        $sut($command);
    }

    public function testShouldThrowBadRequestWhenThreadDoesNotBelongToSession(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $actorId = new UserId(Uuid::v7());
        $sut = $this->buildHandler($sessions, $threads);

        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Session',
            new DateTimeImmutable(self::NOW),
        );
        $sessions->save($session);

        $otherSessionId = ChatSessionId::generate();
        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $otherSessionId,
            'Topic',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($thread);

        $command = $this->command($actorId, $session->id(), $thread->id());

        $this->expectException(BadRequestHttpException::class);

        $sut($command);
    }

    public function testShouldThrowBadRequestWhenReplyToMessageBelongsToDifferentThread(): void
    {
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $audit = new SpyAuditLogger();
        $actorId = new UserId(Uuid::v7());
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            $messages,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );

        $session = ChatSession::create(
            ChatSessionId::generate(),
            OrganizationId::generate(),
            $actorId,
            'Session',
            new DateTimeImmutable(self::NOW),
        );
        $sessions->save($session);

        $threadA = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Topic A',
            new DateTimeImmutable(self::NOW),
        );
        $threadB = ChatThread::create(
            ChatThreadId::generate(),
            $session->id(),
            'Topic B',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($threadA);
        $threads->save($threadB);

        $foreignMessage = $sut($this->command($actorId, $session->id(), $threadB->id()));

        $command = $this->command($actorId, $session->id(), $threadA->id(), replyToMessageId: $foreignMessage->id());

        $this->expectException(BadRequestHttpException::class);

        $sut($command);
    }

    private function buildHandler(
        ?InMemoryChatSessionRepository $sessions = null,
        ?InMemoryChatThreadRepository $threads = null,
        ?InMemoryChatMessageRepository $messages = null,
        ?SpyAuditLogger $audit = null,
    ): SendMessageCommandHandler {
        return new SendMessageCommandHandler(
            $sessions ?? new InMemoryChatSessionRepository(),
            $threads ?? new InMemoryChatThreadRepository(),
            $messages ?? new InMemoryChatMessageRepository(),
            $audit ?? new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );
    }

    private function command(
        UserId $actorId,
        ChatSessionId $sessionId,
        ChatThreadId $threadId,
        ?ChatMessageId $replyToMessageId = null,
    ): SendMessageCommand {
        return new SendMessageCommand(
            actorId: $actorId,
            sessionId: $sessionId,
            threadId: $threadId,
            senderType: ParticipantType::User,
            senderId: $actorId->value,
            recipientType: ParticipantType::Agent,
            recipientId: Uuid::v7(),
            subject: 'Hello',
            priority: Priority::P2,
            content: 'Hello body',
            contentType: ContentType::Text,
            metadata: [],
            replyToMessageId: $replyToMessageId,
            engineId: Uuid::v7(),
            engineType: EngineType::CliTerminal,
            classifiedBy: ClassifiedBy::Sender,
            classificationConfidence: null,
        );
    }
}
