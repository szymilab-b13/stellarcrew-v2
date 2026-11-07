<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;

final class SpyAuditLogger implements AuditLoggerInterface
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        $this->entries[] = $entry;
    }
}
