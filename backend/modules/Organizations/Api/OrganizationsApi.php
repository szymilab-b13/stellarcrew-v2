<?php

declare(strict_types=1);

namespace Modules\Organizations\Api;

use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

interface OrganizationsApi
{
    public function findById(OrganizationId $id): ?OrganizationView;

    public function findByOwner(UserId $ownerUserId): ?OrganizationView;

    public function findBySlug(string $slug): ?OrganizationView;

    public function exists(OrganizationId $id): bool;
}
