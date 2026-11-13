<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Agents;

use DateTimeImmutable;
use Modules\Organizations\Api\Dto\OrganizationView;
use Modules\Organizations\Api\OrganizationsApi;
use Modules\Shared\Kernel\OrganizationId;
use Modules\Shared\Kernel\UserId;

final class FakeOrganizationsApi implements OrganizationsApi
{
    /** @var array<string, OrganizationView> keyed by organization id */
    private array $byId = [];

    /** @var array<string, OrganizationView> keyed by owner user id */
    private array $byOwner = [];

    public function withOrganization(OrganizationId $id, UserId $ownerUserId): self
    {
        $view = new OrganizationView(
            id: $id,
            name: 'Fake Org',
            slug: 'fake-org',
            ownerUserId: $ownerUserId,
            createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );

        $this->byId[(string) $id] = $view;
        $this->byOwner[(string) $ownerUserId] = $view;

        return $this;
    }

    public function findById(OrganizationId $id): ?OrganizationView
    {
        return $this->byId[(string) $id] ?? null;
    }

    public function findByOwner(UserId $ownerUserId): ?OrganizationView
    {
        return $this->byOwner[(string) $ownerUserId] ?? null;
    }

    public function findBySlug(string $slug): ?OrganizationView
    {
        return null;
    }

    public function exists(OrganizationId $id): bool
    {
        return isset($this->byId[(string) $id]);
    }
}
