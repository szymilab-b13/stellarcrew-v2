<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Audit;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Audit\Api\UndoerInterface;
use Modules\Shared\Kernel\UserId;

final class StubTestUndoer implements UndoerInterface
{
    public const string HAPPY_ACTION = 'test.undoable.happy';

    public const string CONFLICT_ACTION = 'test.undoable.conflict';

    public function supports(string $action): bool
    {
        return $action === self::HAPPY_ACTION || $action === self::CONFLICT_ACTION;
    }

    public function undo(AuditEntryDto $entry, UserId $performedBy): void
    {
        if ($entry->action === self::CONFLICT_ACTION) {
            throw new UndoConflictException('test_conflict');
        }
    }
}
