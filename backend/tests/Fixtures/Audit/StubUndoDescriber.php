<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\UndoDescriberInterface;

final readonly class StubUndoDescriber implements UndoDescriberInterface
{
    public function __construct(
        private string $supports,
        private string $text,
    ) {
    }

    public function supports(string $action): bool
    {
        return $action === $this->supports;
    }

    public function describe(AuditEntryDto $entry): string
    {
        return $this->text;
    }
}
