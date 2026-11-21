<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\RefreshTokenGenerator;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Domain\WorkerPairingCodeRepository;
use Modules\Workers\Domain\WorkerRefreshToken;
use Modules\Workers\Domain\WorkerRefreshTokenRepository;
use Modules\Workers\Domain\WorkerRepository;

use function sprintf;
use function str_replace;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ClaimPairingCodeCommandHandler
{
    public function __construct(
        private WorkerPairingCodeRepository $codes,
        private WorkerRepository $workers,
        private WorkerRefreshTokenRepository $refreshTokens,
        private RefreshTokenGenerator $tokenGenerator,
        private Clock $clock,
    ) {
    }

    public function __invoke(ClaimPairingCodeCommand $command): ClaimedPairingResult
    {
        $code = $this->codes->findByCode($command->code);
        if (!$code instanceof WorkerPairingCode) {
            throw new NotFoundHttpException(sprintf('Pairing code "%s" not found.', $command->code));
        }

        $now = $this->clock->now();
        $code->claim($now);

        $workerId = WorkerId::generate();
        $rabbitmqUser = sprintf('worker-%s', str_replace('-', '', (string) $workerId));
        $rabbitmqPassword = $this->tokenGenerator->generate();

        $worker = Worker::create(
            $workerId,
            $code->ownerUserId(),
            $command->name,
            $rabbitmqUser,
            $now,
            $command->hostLabel,
        );

        if ($command->fingerprintHash !== null) {
            $worker->updateFingerprint($command->fingerprintHash, $command->fingerprintMeta, $now);
        }

        $refreshPlaintext = $this->tokenGenerator->generate();
        $refreshToken = WorkerRefreshToken::issue(
            $this->tokenGenerator->hash($refreshPlaintext),
            $workerId,
            $now,
        );

        $this->codes->save($code);
        $this->workers->save($worker);
        $this->refreshTokens->save($refreshToken);

        return new ClaimedPairingResult($workerId, $rabbitmqPassword, $rabbitmqUser, $refreshPlaintext);
    }
}
