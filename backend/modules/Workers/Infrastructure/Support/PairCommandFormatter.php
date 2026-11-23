<?php

declare(strict_types=1);

namespace Modules\Workers\Infrastructure\Support;

use function rtrim;
use function sprintf;

final readonly class PairCommandFormatter
{
    public function __construct(
        private string $publicBaseUrl,
    ) {
    }

    public function format(string $code): string
    {
        $base = rtrim($this->publicBaseUrl, '/');

        return sprintf(
            'curl -fsSL %s/install-worker.sh | bash -s -- --code %s',
            $base,
            $code,
        );
    }
}
