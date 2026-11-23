<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Domain\WorkerPairingCodeRepository;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CancelPairingCodeCommandHandler
{
    public function __construct(
        private WorkerPairingCodeRepository $codes,
    ) {
    }

    public function __invoke(CancelPairingCodeCommand $command): void
    {
        $code = $this->codes->findByCode($command->code);
        if (!$code instanceof WorkerPairingCode) {
            throw new NotFoundHttpException(sprintf('Pairing code "%s" not found.', $command->code));
        }

        if (!$code->ownerUserId()->equals($command->actorUserId)) {
            throw new AccessDeniedHttpException(sprintf('Cannot cancel pairing code "%s".', $command->code));
        }

        if ($code->isClaimed()) {
            throw new ConflictHttpException(sprintf('Pairing code "%s" already claimed.', $command->code));
        }

        $this->codes->delete($code);
    }
}
