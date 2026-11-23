<?php

declare(strict_types=1);

namespace App\Tests\Modules\Chat\Application;

use App\Tests\Fixtures\Agents\InMemoryAgentsApi;
use App\Tests\Fixtures\Chat\InMemoryChatMessageRepository;
use App\Tests\Fixtures\Chat\InMemoryChatSessionRepository;
use App\Tests\Fixtures\Chat\InMemoryChatThreadRepository;
use App\Tests\Fixtures\Chat\SpyMessageBus;
use App\Tests\Fixtures\Core\SpyAuditLogger;
use DateTimeImmutable;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Chat\Application\AgentReplyRequested;
use Modules\Chat\Application\SendMessageCommand;
use Modules\Chat\Application\SendMessageCommandHandler;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\ChatSessionId;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\FrozenClock;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
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
        $bus = new SpyMessageBus();
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            $messages,
            $audit,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
            $bus,
            new InMemoryAgentsApi(),
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
        self::assertSame([], $bus->envelopes, 'No agent reply dispatched when thread has no worker.');
    }

    public function testShouldDispatchAgentReplyWhenThreadHasCurrentWorkerAndRecipientIsAgent(): void
    {
        $actorId = new UserId(Uuid::v7());
        $workerId = WorkerId::generate();
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $messages = new InMemoryChatMessageRepository();
        $bus = new SpyMessageBus();
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            $messages,
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
            $bus,
            new InMemoryAgentsApi(),
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
            'Thread with worker',
            new DateTimeImmutable(self::NOW),
        );
        $thread->assignCurrentWorker($workerId, new DateTimeImmutable(self::NOW));

        $threads->save($thread);

        $command = $this->command($actorId, $session->id(), $thread->id());

        $message = $sut($command);

        self::assertCount(1, $bus->envelopes);
        $envelope = $bus->envelopes[0];
        $event = $envelope->getMessage();
        self::assertInstanceOf(AgentReplyRequested::class, $event);
        self::assertTrue($event->threadId->equals($thread->id()));
        self::assertTrue($event->workerId->equals($workerId));
        self::assertTrue($event->agentId->equals($command->recipientId));
        self::assertTrue($event->userMessageId->equals($message->id()));
        self::assertSame('Hello body', $event->userContent);
        self::assertSame('Hello', $event->subject);

        $stamp = $envelope->last(AmqpStamp::class);
        self::assertInstanceOf(AmqpStamp::class, $stamp);
        self::assertSame((string) $workerId, $stamp->getRoutingKey());
    }

    public function testShouldAssignAgentDefaultWorkerToThreadAndDispatchAgentReply(): void
    {
        $actorId = new UserId(Uuid::v7());
        $defaultWorkerId = WorkerId::generate();
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $bus = new SpyMessageBus();
        $agentId = AgentId::generate();
        $agents = new InMemoryAgentsApi([
            new AgentView(
                id: $agentId,
                organizationId: OrganizationId::generate(),
                ownerUserId: $actorId,
                sourceTemplateId: null,
                slug: 'bot',
                name: 'Bot',
                role: 'Assistant',
                roleDescription: 'desc',
                expertise: 'expertise',
                workflow: 'workflow',
                style: 'style',
                outputFormat: 'format',
                isActive: true,
                createdAt: new DateTimeImmutable(self::NOW),
                updatedAt: new DateTimeImmutable(self::NOW),
                defaultWorkerId: $defaultWorkerId,
            ),
        ]);
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            new InMemoryChatMessageRepository(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
            $bus,
            $agents,
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
            'Thread without worker',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($thread);

        $command = new SendMessageCommand(
            actorId: $actorId,
            sessionId: $session->id(),
            threadId: $thread->id(),
            senderType: ParticipantType::User,
            senderId: $actorId->value,
            recipientType: ParticipantType::Agent,
            recipientId: $agentId->value,
            subject: 'Hello',
            priority: Priority::P2,
            content: 'Hello body',
            contentType: ContentType::Text,
            metadata: [],
            replyToMessageId: null,
            engineId: Uuid::v7(),
            engineType: EngineType::CliTerminal,
            classifiedBy: ClassifiedBy::Sender,
            classificationConfidence: null,
        );

        $sut($command);

        $savedThread = $threads->findById($thread->id());
        self::assertNotNull($savedThread);
        self::assertTrue($savedThread->currentWorkerId()?->equals($defaultWorkerId));
        self::assertCount(1, $bus->envelopes);
        $envelope = $bus->envelopes[0];
        $event = $envelope->getMessage();
        self::assertInstanceOf(AgentReplyRequested::class, $event);
        self::assertTrue($event->workerId->equals($defaultWorkerId));
    }

    public function testShouldNotDispatchAgentReplyWhenAgentHasNoDefaultWorker(): void
    {
        $actorId = new UserId(Uuid::v7());
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $bus = new SpyMessageBus();
        $agentId = AgentId::generate();
        $agents = new InMemoryAgentsApi([
            new AgentView(
                id: $agentId,
                organizationId: OrganizationId::generate(),
                ownerUserId: $actorId,
                sourceTemplateId: null,
                slug: 'bot',
                name: 'Bot',
                role: 'Assistant',
                roleDescription: 'desc',
                expertise: 'expertise',
                workflow: 'workflow',
                style: 'style',
                outputFormat: 'format',
                isActive: true,
                createdAt: new DateTimeImmutable(self::NOW),
                updatedAt: new DateTimeImmutable(self::NOW),
            ),
        ]);
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            new InMemoryChatMessageRepository(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
            $bus,
            $agents,
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
            'Thread without worker',
            new DateTimeImmutable(self::NOW),
        );
        $threads->save($thread);

        $sut(new SendMessageCommand(
            actorId: $actorId,
            sessionId: $session->id(),
            threadId: $thread->id(),
            senderType: ParticipantType::User,
            senderId: $actorId->value,
            recipientType: ParticipantType::Agent,
            recipientId: $agentId->value,
            subject: 'Hello',
            priority: Priority::P2,
            content: 'Hello body',
            contentType: ContentType::Text,
            metadata: [],
            replyToMessageId: null,
            engineId: Uuid::v7(),
            engineType: EngineType::CliTerminal,
            classifiedBy: ClassifiedBy::Sender,
            classificationConfidence: null,
        ));

        self::assertSame([], $bus->envelopes);
        $savedThread = $threads->findById($thread->id());
        self::assertNotNull($savedThread);
        self::assertNull($savedThread->currentWorkerId());
    }

    public function testShouldNotDispatchAgentReplyWhenRecipientIsNotAgent(): void
    {
        $actorId = new UserId(Uuid::v7());
        $otherUserId = new UserId(Uuid::v7());
        $workerId = WorkerId::generate();
        $sessions = new InMemoryChatSessionRepository();
        $threads = new InMemoryChatThreadRepository();
        $bus = new SpyMessageBus();
        $sut = new SendMessageCommandHandler(
            $sessions,
            $threads,
            new InMemoryChatMessageRepository(),
            new SpyAuditLogger(),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
            $bus,
            new InMemoryAgentsApi(),
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
            'DM thread',
            new DateTimeImmutable(self::NOW),
        );
        $thread->assignCurrentWorker($workerId, new DateTimeImmutable(self::NOW));

        $threads->save($thread);

        $command = new SendMessageCommand(
            actorId: $actorId,
            sessionId: $session->id(),
            threadId: $thread->id(),
            senderType: ParticipantType::User,
            senderId: $actorId->value,
            recipientType: ParticipantType::User,
            recipientId: $otherUserId->value,
            subject: 'Hi',
            priority: Priority::P2,
            content: 'Hey',
            contentType: ContentType::Text,
            metadata: [],
            replyToMessageId: null,
            engineId: Uuid::v7(),
            engineType: EngineType::CliTerminal,
            classifiedBy: ClassifiedBy::Sender,
            classificationConfidence: null,
        );

        $sut($command);

        self::assertSame([], $bus->envelopes);
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
            new SpyMessageBus(),
            new InMemoryAgentsApi(),
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
            new SpyMessageBus(),
            new InMemoryAgentsApi(),
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
