<?php

declare(strict_types=1);

namespace Modules\Audit\Domain;

use Modules\Shared\Kernel\AuditEntryId;

interface AuditLogEntryRepository
{
    public function save(AuditLogEntry $entry): void;

    public function findById(AuditEntryId $id): ?AuditLogEntry;
}
