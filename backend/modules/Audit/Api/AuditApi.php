<?php

declare(strict_types=1);

namespace Modules\Audit\Api;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditPage;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

interface AuditApi
{
    public function record(AuditEntryWithState $entry): AuditEntryId;

    public function search(AuditQuery $query): AuditPage;

    public function get(AuditEntryId $id): ?AuditEntryDto;

    /**
     * Returns the most recent entries related to the user — either as actor or as a target resource.
     *
     * @return list<AuditEntryDto>
     */
    public function listRecentForUser(UserId $userId, int $limit = 20): array;

    public function undo(AuditEntryId $id, UserId $performedBy): AuditEntryId;

    public function describeUndo(AuditEntryId $id): string;
}
