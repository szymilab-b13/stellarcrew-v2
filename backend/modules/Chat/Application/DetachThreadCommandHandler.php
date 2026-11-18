<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadNotFoundException;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class DetachThreadCommandHandler
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatSessionRepository $sessions,
        private Clock $clock,
    ) {
    }

    public function __invoke(DetachThreadCommand $command): ChatThread
    {
        $thread = $this->threads->findById($command->threadId);
        if (!$thread instanceof ChatThread) {
            throw new NotFoundHttpException(ChatThreadNotFoundException::forId($command->threadId)->getMessage());
        }

        $session = $this->sessions->findById($thread->sessionId());
        if (!$session instanceof ChatSession) {
            throw new NotFoundHttpException(ChatSessionNotFoundException::forId($thread->sessionId())->getMessage());
        }

        if (!$session->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(sprintf(
                'Cannot detach thread "%s".',
                (string) $command->threadId,
            ));
        }

        $thread->detachFromParent($this->clock->now());

        $this->threads->save($thread);

        return $thread;
    }
}
