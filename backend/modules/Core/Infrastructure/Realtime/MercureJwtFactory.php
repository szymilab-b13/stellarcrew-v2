<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Realtime;

use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;

final readonly class MercureJwtFactory
{
    public function __construct(
        private string $secret,
    ) {
        if ($this->secret === '') {
            throw new InvalidArgumentException('MercureJwtFactory requires a non-empty secret.');
        }
    }

    /**
     * @param list<string> $publishTopics
     * @param list<string> $subscribeTopics
     */
    public function issue(array $publishTopics, array $subscribeTopics): string
    {
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::default());
        $now = new DateTimeImmutable();

        $token = $builder
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('mercure', [
                'publish' => $publishTopics,
                'subscribe' => $subscribeTopics,
            ])
            ->getToken(new Sha256(), InMemory::plainText($this->secret));

        return $token->toString();
    }
}
