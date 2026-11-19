<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\UserId;
use Modules\Shared\Kernel\WorkerId;

final readonly class AcceptFingerprintCommand
{
    /**
     * @param array<string, mixed>|null $fingerprintMeta
     */
    public function __construct(
        public WorkerId $workerId,
        public UserId $actorId,
        public string $fingerprintHash,
        public ?array $fingerprintMeta = null,
    ) {
    }
}
