<?php

declare(strict_types=1);

namespace Modules\Workers\Application;

use Modules\Shared\Kernel\Clock;
use Modules\Workers\Domain\WorkerPairingCode;
use Modules\Workers\Domain\WorkerPairingCodeRepository;
use Modules\Workers\Infrastructure\Support\PairingCodeGenerator;

final readonly class GeneratePairingCodeCommandHandler
{
    public function __construct(
        private WorkerPairingCodeRepository $codes,
        private PairingCodeGenerator $generator,
        private Clock $clock,
    ) {
    }

    public function __invoke(GeneratePairingCodeCommand $command): WorkerPairingCode
    {
        $now = $this->clock->now();
        $code = WorkerPairingCode::issue($this->generator->generate(), $command->ownerUserId, $now);
        $this->codes->save($code);

        return $code;
    }
}
