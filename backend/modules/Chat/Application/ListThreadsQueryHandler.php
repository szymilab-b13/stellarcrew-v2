<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadRepository;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class ListThreadsQueryHandler
{
    public function __construct(
        private ChatSessionRepository $sessions,
        private ChatThreadRepository $threads,
    ) {
    }

    /**
     * @return list<ChatThread>
     */
    public function __invoke(ListThreadsQuery $query): array
    {
        $session = $this->sessions->findById($query->sessionId);
        if (!$session instanceof ChatSession) {
            throw new NotFoundHttpException(ChatSessionNotFoundException::forId($query->sessionId)->getMessage());
        }

        if (!$session->ownerUserId()->equals($query->actorId)) {
            throw new AccessDeniedException(sprintf(
                'Cannot list threads for session "%s".',
                (string) $query->sessionId,
            ));
        }

        return $this->threads->findBySession($query->sessionId);
    }
}
