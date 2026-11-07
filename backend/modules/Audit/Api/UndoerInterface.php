<?php

declare(strict_types=1);

namespace Modules\Audit\Api;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Exception\UndoConflictException;
use Modules\Shared\Kernel\UserId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('audit.undoer')]
interface UndoerInterface
{
    public function supports(string $action): bool;

    /**
     * @throws UndoConflictException when the undo cannot proceed because the current state diverged.
     */
    public function undo(AuditEntryDto $entry, UserId $performedBy): void;
}
