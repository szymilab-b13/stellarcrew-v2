<?php

declare(strict_types=1);

namespace Modules\Audit\Api;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('audit.undo_describer')]
interface UndoDescriberInterface
{
    public function supports(string $action): bool;

    public function describe(AuditEntryDto $entry): string;
}
