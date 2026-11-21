<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\UserId;

final readonly class CancelPairingCodeCommand
{
    public function __construct(
        public string $code,
        public UserId $actorUserId,
    ) {
    }
}
