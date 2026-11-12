<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\Api;

use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final readonly class DefaultOrganizationsApi implements OrganizationsApi
{
    public function __construct(private OrganizationRepository $organizations)
    {
    }

    public function findById(OrganizationId $id): ?OrganizationView
    {
        $organization = $this->organizations->findById($id);
        if (!$organization instanceof Organization || $organization->isDeleted()) {
            return null;
        }

        return $this->toView($organization);
    }

    public function findByOwner(UserId $ownerUserId): ?OrganizationView
    {
        $organization = $this->organizations->findByOwner($ownerUserId);
        if (!$organization instanceof Organization) {
            return null;
        }

        return $this->toView($organization);
    }

    public function findBySlug(string $slug): ?OrganizationView
    {
        $organization = $this->organizations->findBySlug($slug);
        if (!$organization instanceof Organization) {
            return null;
        }

        return $this->toView($organization);
    }

    public function exists(OrganizationId $id): bool
    {
        $organization = $this->organizations->findById($id);

        return $organization instanceof Organization && !$organization->isDeleted();
    }

    private function toView(Organization $organization): OrganizationView
    {
        return new OrganizationView(
            id: $organization->id(),
            name: $organization->name(),
            slug: $organization->slug(),
            ownerUserId: $organization->ownerUserId(),
            createdAt: $organization->createdAt(),
            updatedAt: $organization->updatedAt(),
        );
    }
}
