<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use InvalidArgumentException;

use function is_string;

use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class WorkerAuthenticator
{
    public function __construct(
        private WorkerRepository $workers,
        private WorkerSecretTokenHasher $hasher,
    ) {
    }

    public function authenticate(Request $request): Worker
    {
        $workerIdHeader = $request->headers->get('X-Worker-Id');
        $secretHeader = $request->headers->get('X-Worker-Secret');

        if (!is_string($workerIdHeader) || !is_string($secretHeader) || $workerIdHeader === '' || $secretHeader === '') {
            throw new AccessDeniedHttpException('Missing worker credentials.');
        }

        try {
            $workerId = WorkerId::fromString($workerIdHeader);
        } catch (InvalidArgumentException) {
            throw new AccessDeniedHttpException('Malformed X-Worker-Id.');
        }

        $worker = $this->workers->findById($workerId);
        if ($worker === null) {
            throw new AccessDeniedHttpException('Unknown worker.');
        }

        if (!$this->hasher->verify($secretHeader, $worker->secretTokenHash())) {
            throw new AccessDeniedHttpException('Invalid worker credentials.');
        }

        return $worker;
    }
}
