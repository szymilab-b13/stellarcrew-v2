<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatMessageRepository;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadNotFoundException;
use Modules\Chat\Domain\ChatThreadRepository;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class ListMessagesQueryHandler
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatSessionRepository $sessions,
        private ChatMessageRepository $messages,
    ) {
    }

    public function __invoke(ListMessagesQuery $query): ListMessagesResult
    {
        $thread = $this->threads->findById($query->threadId);
        if (!$thread instanceof ChatThread) {
            throw new NotFoundHttpException(ChatThreadNotFoundException::forId($query->threadId)->getMessage());
        }

        $session = $this->sessions->findById($thread->sessionId());
        if (!$session instanceof ChatSession) {
            throw new NotFoundHttpException(ChatSessionNotFoundException::forId($thread->sessionId())->getMessage());
        }

        if (!$session->ownerUserId()->equals($query->actorId)) {
            throw new AccessDeniedException(sprintf(
                'Cannot list messages for thread "%s".',
                (string) $query->threadId,
            ));
        }

        return new ListMessagesResult(
            messages: $this->messages->findByThreadPaginated($query->threadId, $query->limit, $query->offset),
            total: $this->messages->countByThread($query->threadId),
            limit: $query->limit,
            offset: $query->offset,
        );
    }
}
