<?php

declare(strict_types=1);

namespace Modules\Audit\Application\ReadModel;

use Modules\Audit\Api\Dto\AuditEntryDto;
use Modules\Audit\Api\Dto\AuditPage;
use Modules\Audit\Api\Dto\AuditQuery;
use Modules\Shared\Kernel\AuditEntryId;
use Modules\Shared\Kernel\UserId;

interface AuditEntriesReadModel
{
    public function search(AuditQuery $query): AuditPage;

    public function find(AuditEntryId $id): ?AuditEntryDto;

    /**
     * @return list<AuditEntryDto>
     */
    public function listRecentForUser(UserId $userId, int $limit): array;
}
