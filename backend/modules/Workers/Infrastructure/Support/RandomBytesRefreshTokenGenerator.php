<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use Modules\Workers\Domain\RefreshTokenGenerator;

use function base64_encode;
use function hash;
use function random_bytes;
use function rtrim;
use function strtr;

final readonly class RandomBytesRefreshTokenGenerator implements RefreshTokenGenerator
{
    public const int TOKEN_BYTES = 32;

    public function generate(): string
    {
        $raw = random_bytes(self::TOKEN_BYTES);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function hash(string $plaintext): string
    {
        return hash('sha256', $plaintext);
    }
}
