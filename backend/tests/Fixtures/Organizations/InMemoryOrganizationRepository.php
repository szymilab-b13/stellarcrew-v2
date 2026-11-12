<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Organizations;

use function count;

use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final class InMemoryOrganizationRepository implements OrganizationRepository
{
    /** @var array<string, Organization> keyed by organization id */
    private array $organizations = [];

    public function save(Organization $organization): void
    {
        $this->organizations[(string) $organization->id()] = $organization;
    }

    public function findById(OrganizationId $id): ?Organization
    {
        return $this->organizations[(string) $id] ?? null;
    }

    public function findByOwner(UserId $ownerUserId): ?Organization
    {
        foreach ($this->organizations as $organization) {
            if ($organization->isDeleted()) {
                continue;
            }

            if ($organization->ownerUserId()->equals($ownerUserId)) {
                return $organization;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?Organization
    {
        foreach ($this->organizations as $organization) {
            if ($organization->isDeleted()) {
                continue;
            }

            if ($organization->slug() === $slug) {
                return $organization;
            }
        }

        return null;
    }

    public function existsSlug(string $slug): bool
    {
        return $this->findBySlug($slug) instanceof Organization;
    }

    public function count(): int
    {
        return count($this->organizations);
    }
}
