<?php

declare(strict_types=1);

namespace Modules\Chat\Application;

use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Chat\Domain\ChatThreadWorkerSession;
use Modules\Chat\Domain\ChatThreadWorkerSessionRepository;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final readonly class GetOrCreateThreadWorkerSessionCommandHandler
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatThreadWorkerSessionRepository $sessions,
        private Clock $clock,
    ) {
    }

    public function __invoke(GetOrCreateThreadWorkerSessionCommand $command): ChatThreadWorkerSession
    {
        $existing = $this->sessions->findByThreadAndWorker($command->threadId, $command->workerId);
        if ($existing !== null) {
            return $existing;
        }

        $thread = $this->threads->findById($command->threadId);
        if ($thread === null) {
            throw new NotFoundHttpException(sprintf('Chat thread "%s" not found.', $command->threadId));
        }

        $session = ChatThreadWorkerSession::start(
            threadId: $command->threadId,
            workerId: $command->workerId,
            claudeSessionId: Uuid::v7(),
            now: $this->clock->now(),
        );
        $this->sessions->save($session);

        return $session;
    }
}
