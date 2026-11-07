<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Shared\Kernel\UserId;

final class StubUndoer implements UndoerInterface
{
    /** @var list<array{entry: AuditEntryDto, performedBy: UserId}> */
    public array $calls = [];

    public function __construct(
        private readonly string $supports,
        private readonly ?UndoConflictException $throws = null,
    ) {
    }

    public function supports(string $action): bool
    {
        return $action === $this->supports;
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        $this->calls[] = ['entry' => $entry, 'performedBy' => $performedBy];

        if ($this->throws instanceof UndoConflictException) {
            throw $this->throws;
        }
    }
}
