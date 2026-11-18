<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatSession;
use Modules\Chat\Domain\ChatSessionNotFoundException;
use Modules\Chat\Domain\ChatSessionRepository;
use Modules\Chat\Domain\ChatThread;
use Modules\Chat\Domain\ChatThreadNotFoundException;
use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Shared\Kernel\ChatThreadId;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CreateThreadCommandHandler
{
    public function __construct(
        private ChatSessionRepository $sessions,
        private ChatThreadRepository $threads,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateThreadCommand $command): ChatThread
    {
        $session = $this->sessions->findById($command->sessionId);
        if (!$session instanceof ChatSession) {
            throw new NotFoundHttpException(ChatSessionNotFoundException::forId($command->sessionId)->getMessage());
        }

        if (!$session->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(sprintf(
                'Cannot create thread in session "%s".',
                (string) $command->sessionId,
            ));
        }

        if ($command->parentThreadId instanceof ChatThreadId) {
            $parent = $this->threads->findById($command->parentThreadId);
            if (!$parent instanceof ChatThread) {
                throw new NotFoundHttpException(
                    ChatThreadNotFoundException::forId($command->parentThreadId)->getMessage(),
                );
            }

            if (!$parent->sessionId()->equals($command->sessionId)) {
                throw new BadRequestHttpException(sprintf(
                    'Parent thread "%s" does not belong to session "%s".',
                    (string) $command->parentThreadId,
                    (string) $command->sessionId,
                ));
            }
        }

        $thread = ChatThread::create(
            ChatThreadId::generate(),
            $command->sessionId,
            $command->subject,
            $this->clock->now(),
            $command->parentThreadId,
        );

        $this->threads->save($thread);

        return $thread;
    }
}
