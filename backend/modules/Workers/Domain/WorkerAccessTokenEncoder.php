<?php

declare(strict_types=1);

namespace Modules\Workers\Domain;

use DateTimeImmutable;
use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

interface WorkerAccessTokenEncoder
{
    /**
     * Encodes a worker access token (short-lived JWT).
     *
     * @return string signed JWT (RS256)
     */
    public function encode(
        WorkerId $workerId,
        UserId $ownerUserId,
        DateTimeImmutable $issuedAt,
        int $ttlSeconds,
    ): string;
}
