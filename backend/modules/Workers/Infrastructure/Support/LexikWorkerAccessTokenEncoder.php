<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;
use Modules\Workers\Domain\WorkerAccessTokenEncoder;

final readonly class LexikWorkerAccessTokenEncoder implements WorkerAccessTokenEncoder
{
    public const string DEFAULT_SCOPE = 'worker:session.read worker:messages.write';

    public function __construct(
        private JWTEncoderInterface $encoder,
        private string $issuer = 'stellarcrew-api',
        private string $audience = 'stellarcrew-api',
        private string $scope = self::DEFAULT_SCOPE,
    ) {
    }

    public function encode(
        WorkerId $workerId,
        UserId $ownerUserId,
        DateTimeImmutable $issuedAt,
        int $ttlSeconds,
    ): string {
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => 'worker:' . $workerId,
            'owner_user_id' => (string) $ownerUserId,
            'scope' => $this->scope,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $issuedAt->getTimestamp() + $ttlSeconds,
        ];

        return $this->encoder->encode($payload);
    }
}
