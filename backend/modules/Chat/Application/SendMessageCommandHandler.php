<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Agents\Api\AgentsApi;
use Modules\Agents\Api\Dto\AgentView;
use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatMessageNotFoundException;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadNotFoundException;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Core\Api\RealtimePublisher;
use Modules\Shared\Kernel\AgentId;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkerId;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class SendMessageCommandHandler
{
    public const string AUDIT_ACTION = 'chat.message.sent';

    public const string AUDIT_RESOURCE_TYPE = 'chat_message';

    public function __construct(
        private ChatSessionRepository $sessions,
        private ChatThreadRepository $threads,
        private ChatMessageRepository $messages,
        private AuditLoggerInterface $audit,
        private Clock $clock,
        private MessageBusInterface $agentReplyBus,
        private AgentsApi $agents,
        private RealtimePublisher $realtime,
    ) {
    }

    public function __invoke(SendMessageCommand $command): ChatMessage
    {
        $session = $this->sessions->findById($command->sessionId);
        if (!$session instanceof ChatSession) {
            throw new NotFoundHttpException(ChatSessionNotFoundException::forId($command->sessionId)->getMessage());
        }

        if (!$session->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(sprintf(
                'Cannot send message to session "%s".',
                (string) $command->sessionId,
            ));
        }

        $thread = $this->threads->findById($command->threadId);
        if (!$thread instanceof ChatThread) {
            throw new NotFoundHttpException(ChatThreadNotFoundException::forId($command->threadId)->getMessage());
        }

        if (!$thread->sessionId()->equals($command->sessionId)) {
            throw new BadRequestHttpException(sprintf(
                'Thread "%s" does not belong to session "%s".',
                (string) $command->threadId,
                (string) $command->sessionId,
            ));
        }

        if ($command->replyToMessageId instanceof ChatMessageId) {
            $reply = $this->messages->findById($command->replyToMessageId);
            if (!$reply instanceof ChatMessage) {
                throw new NotFoundHttpException(
                    ChatMessageNotFoundException::forId($command->replyToMessageId)->getMessage(),
                );
            }

            if (!$reply->threadId()->equals($command->threadId)) {
                throw new BadRequestHttpException(sprintf(
                    'Reply target message "%s" does not belong to thread "%s".',
                    (string) $command->replyToMessageId,
                    (string) $command->threadId,
                ));
            }
        }

        $now = $this->clock->now();
        $message = ChatMessage::send(
            ChatMessageId::generate(),
            $command->sessionId,
            $command->threadId,
            $command->senderType,
            $command->senderId,
            $command->recipientType,
            $command->recipientId,
            $command->subject,
            $command->priority,
            $command->content,
            $command->contentType,
            $command->metadata,
            $command->replyToMessageId,
            $command->engineId,
            $command->engineType,
            $command->classifiedBy,
            $command->classificationConfidence,
            $now,
        );

        $this->messages->save($message);

        $this->audit->log(new AuditEntry(
            userId: $command->actorId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $message->id(),
            metadata: [
                'after_state' => self::snapshot($message),
            ],
        ));

        $this->dispatchAgentReplyIfNeeded($command, $thread, $message);

        $this->realtime->publish(
            sprintf('/chat/thread/%s', (string) $command->threadId),
            [
                'type' => 'message.sent',
                'threadId' => (string) $command->threadId,
                'messageId' => (string) $message->id(),
            ],
        );

        return $message;
    }

    private function dispatchAgentReplyIfNeeded(
        SendMessageCommand $command,
        ChatThread $thread,
        ChatMessage $message,
    ): void {
        if ($command->senderType !== ParticipantType::User) {
            return;
        }

        if ($command->recipientType !== ParticipantType::Agent) {
            return;
        }

        if ($command->engineType === EngineType::StellarcrewWorker) {
            $workerId = new WorkerId($command->engineId);
            $currentWorkerId = $thread->currentWorkerId();
            if (!$currentWorkerId instanceof WorkerId || !$currentWorkerId->equals($workerId)) {
                $thread->assignCurrentWorker($workerId, $this->clock->now());
                $this->threads->save($thread);
            }
        } else {
            $workerId = $thread->currentWorkerId();
            if (!$workerId instanceof WorkerId) {
                $agent = $this->agents->findById(new AgentId($command->recipientId));
                if (!$agent instanceof AgentView || !$agent->defaultWorkerId instanceof WorkerId) {
                    return;
                }

                $workerId = $agent->defaultWorkerId;
                $thread->assignCurrentWorker($workerId, $this->clock->now());
                $this->threads->save($thread);
            }
        }

        $this->agentReplyBus->dispatch(
            new Envelope(
                new AgentReplyRequested(
                    threadId: $command->threadId,
                    workerId: $workerId,
                    agentId: $command->recipientId,
                    userMessageId: $message->id(),
                    userContent: $command->content,
                    subject: $command->subject,
                ),
                [new AmqpStamp((string) $workerId)],
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(ChatMessage $message): array
    {
        return [
            'id' => (string) $message->id(),
            'session_id' => (string) $message->sessionId(),
            'thread_id' => (string) $message->threadId(),
            'sender_type' => $message->senderType()->value,
            'sender_id' => (string) $message->senderId(),
            'recipient_type' => $message->recipientType()->value,
            'recipient_id' => (string) $message->recipientId(),
            'subject' => $message->subject(),
            'priority' => $message->priority()->value,
            'content_type' => $message->contentType()->value,
            'engine_type' => $message->engineType()->value,
            'engine_id' => (string) $message->engineId(),
            'classified_by' => $message->classifiedBy()->value,
        ];
    }
}
