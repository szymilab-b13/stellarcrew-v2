<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Audit;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;

/**
 * Temporary sink used until the Audit module's concrete logger lands (BL-AUDIT-001/002).
 */
final class NoOpAuditLogger implements AuditLoggerInterface
{
    public function log(AuditEntry $entry): void
    {
        unset($entry);
    }
}
