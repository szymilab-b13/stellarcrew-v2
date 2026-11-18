<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use InvalidArgumentException;
use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadNotFoundException;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class AttachThreadCommandHandler
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatSessionRepository $sessions,
        private Clock $clock,
    ) {
    }

    public function __invoke(AttachThreadCommand $command): ChatThread
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
                'Cannot attach thread "%s".',
                (string) $command->threadId,
            ));
        }

        $parent = $this->threads->findById($command->parentThreadId);
        if (!$parent instanceof ChatThread) {
            throw new NotFoundHttpException(
                ChatThreadNotFoundException::forId($command->parentThreadId)->getMessage(),
            );
        }

        if (!$parent->sessionId()->equals($thread->sessionId())) {
            throw new BadRequestHttpException(sprintf(
                'Parent thread "%s" does not belong to session "%s".',
                (string) $command->parentThreadId,
                (string) $thread->sessionId(),
            ));
        }

        try {
            $thread->attachToParent($command->parentThreadId, $this->clock->now());
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new BadRequestHttpException($invalidArgumentException->getMessage(), $invalidArgumentException);
        }

        $this->threads->save($thread);

        return $thread;
    }
}
