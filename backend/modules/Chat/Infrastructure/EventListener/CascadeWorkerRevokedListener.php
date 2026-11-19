<?php

declare(strict_types=1);

namespace Modules\Chat\Infrastructure\EventListener;

use Modules\Chat\Domain\ChatThreadRepository;
use Modules\Chat\Domain\ChatThreadWorkerSessionRepository;
use Modules\Workers\Api\Event\WorkerRevoked;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkerRevoked::class)]
final readonly class CascadeWorkerRevokedListener
{
    public function __construct(
        private ChatThreadRepository $threads,
        private ChatThreadWorkerSessionRepository $sessions,
    ) {
    }

    public function __invoke(WorkerRevoked $event): void
    {
        foreach ($this->threads->findByCurrentWorker($event->workerId) as $thread) {
            $thread->assignCurrentWorker(null, $event->occurredAt);
            $this->threads->save($thread);
        }

        $this->sessions->deleteByWorker($event->workerId);
    }
}
