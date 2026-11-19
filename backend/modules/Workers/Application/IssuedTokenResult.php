<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

final readonly class IssuedTokenResult
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public int $expiresIn,
    ) {
    }
}
