<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Core;

use LogicException;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\UserId;

final class SpyAuditLogger implements AuditLoggerInterface
{
    /** @var list<AuditEntry> */
    public array $entries = [];

    public function log(AuditEntry $entry): void
    {
        if (!$entry->userId instanceof UserId) {
            throw new LogicException('AuditService cannot persist entry without actor user id.');
        }

        $this->entries[] = $entry;
    }
}
