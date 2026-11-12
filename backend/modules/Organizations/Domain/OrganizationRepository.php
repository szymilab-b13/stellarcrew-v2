<?php

declare(strict_types=1);

namespace Modules\Organizations\Domain;

use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    public function findById(OrganizationId $id): ?Organization;

    public function findByOwner(UserId $ownerUserId): ?Organization;

    public function findBySlug(string $slug): ?Organization;

    public function existsSlug(string $slug): bool;
}
