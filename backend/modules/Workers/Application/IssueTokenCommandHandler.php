<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Api\Event\WorkerFingerprintChanged;
use Modules\Workers\Domain\RefreshTokenGenerator;
use Modules\Workers\Domain\Worker;
use Modules\Workers\Domain\WorkerAccessTokenEncoder;
use Modules\Workers\Domain\WorkerRefreshToken;
use Modules\Workers\Domain\WorkerRefreshTokenRepository;
use Modules\Workers\Domain\WorkerRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class IssueTokenCommandHandler
{
    public const int ACCESS_TOKEN_TTL_SECONDS = 900;

    public function __construct(
        private WorkerRefreshTokenRepository $refreshTokens,
        private WorkerRepository $workers,
        private RefreshTokenGenerator $tokenGenerator,
        private WorkerAccessTokenEncoder $tokenEncoder,
        private EventDispatcherInterface $events,
        private Clock $clock,
    ) {
    }

    public function __invoke(IssueTokenCommand $command): IssuedTokenResult
    {
        $incomingHash = $this->tokenGenerator->hash($command->refreshToken);
        $stored = $this->refreshTokens->findByTokenHash($incomingHash);
        if (!$stored instanceof WorkerRefreshToken) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid refresh token.');
        }

        $now = $this->clock->now();
        if ($stored->isExpired($now)) {
            throw new UnauthorizedHttpException('Bearer', 'Refresh token expired.');
        }

        if ($stored->isConsumed()) {
            throw new UnauthorizedHttpException('Bearer', 'Refresh token already used.');
        }

        $worker = $this->workers->findById($stored->workerId());
        if (!$worker instanceof Worker) {
            throw new UnauthorizedHttpException('Bearer', 'Worker not found.');
        }

        $storedHash = $worker->fingerprintHash();
        $storedMeta = $worker->fingerprintMeta();
        $fingerprintWarning = false;
        $previousMeta = null;
        if ($command->fingerprintHash !== null && $storedHash !== null && $storedHash !== $command->fingerprintHash) {
            $fingerprintWarning = true;
            $previousMeta = $storedMeta;
            $worker->touchFingerprintMeta($command->fingerprintMeta, $now);
            $this->workers->save($worker);
            $this->events->dispatch(new WorkerFingerprintChanged(
                workerId: $worker->id(),
                ownerUserId: $worker->ownerUserId(),
                previousMeta: $storedMeta,
                currentMeta: $command->fingerprintMeta,
                occurredAt: $now,
            ));
        }

        $stored->markConsumed($now);

        $newRefreshPlain = $this->tokenGenerator->generate();
        $newRefresh = WorkerRefreshToken::issue(
            $this->tokenGenerator->hash($newRefreshPlain),
            $worker->id(),
            $now,
        );

        $this->refreshTokens->save($stored);
        $this->refreshTokens->save($newRefresh);

        $accessToken = $this->tokenEncoder->encode(
            $worker->id(),
            $worker->ownerUserId(),
            $now,
            self::ACCESS_TOKEN_TTL_SECONDS,
        );

        return new IssuedTokenResult(
            accessToken: $accessToken,
            refreshToken: $newRefreshPlain,
            expiresIn: self::ACCESS_TOKEN_TTL_SECONDS,
            fingerprintWarning: $fingerprintWarning,
            previousFingerprintMeta: $previousMeta,
        );
    }
}
