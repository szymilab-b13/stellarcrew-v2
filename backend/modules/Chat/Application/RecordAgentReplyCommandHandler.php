<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatMessage;
use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Chat\Domain\ChatThreadWorkerSessionRepository;
use Modules\Chat\Domain\ClassifiedBy;
use Modules\Chat\Domain\ContentType;
use Modules\Chat\Domain\EngineType;
use Modules\Chat\Domain\ParticipantType;
use Modules\Chat\Domain\Priority;
use Modules\Shared\Kernel\ChatMessageId;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class RecordAgentReplyCommandHandler
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatSessionRepository $chatSessions,
        private ChatMessageRepository $messages,
        private ChatThreadWorkerSessionRepository $sessions,
        private Clock $clock,
    ) {
    }

    public function __invoke(RecordAgentReplyCommand $command): ChatMessage
    {
        $thread = $this->threads->findById($command->threadId);
        if ($thread === null) {
            throw new NotFoundHttpException(sprintf('Chat thread "%s" not found.', $command->threadId));
        }

        $chatSession = $this->chatSessions->findById($thread->sessionId());
        if ($chatSession === null) {
            throw new NotFoundHttpException(sprintf('Chat session "%s" not found.', $thread->sessionId()));
        }

        $now = $this->clock->now();
        $message = ChatMessage::send(
            id: ChatMessageId::generate(),
            sessionId: $thread->sessionId(),
            threadId: $command->threadId,
            senderType: ParticipantType::Agent,
            senderId: $command->agentId,
            recipientType: ParticipantType::User,
            recipientId: $chatSession->ownerUserId()->value,
            subject: $thread->subject(),
            priority: Priority::P2,
            content: $command->content,
            contentType: ContentType::Text,
            metadata: [],
            replyToMessageId: $command->replyToMessageId,
            engineId: $command->agentId,
            engineType: EngineType::CliTerminal,
            classifiedBy: ClassifiedBy::Sender,
            classificationConfidence: null,
            now: $now,
            handledByWorkerId: $command->workerId,
        );
        $this->messages->save($message);

        $session = $this->sessions->findByThreadAndWorker($command->threadId, $command->workerId);
        if ($session !== null) {
            $session->recordMessageAt($now);
            $this->sessions->save($session);
        }

        return $message;
    }
}
