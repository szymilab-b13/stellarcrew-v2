<?php

declare(strict_types=1);

namespace Modules\Organizations\Application;

use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Organizations\Domain\OrganizationSlugGenerator;
use Modules\Organizations\Domain\OwnerAlreadyHasOrganizationException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;

final readonly class CreateOrganizationCommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateOrganizationCommand $command): Organization
    {
        if ($this->organizations->findByOwner($command->ownerUserId) instanceof Organization) {
            throw OwnerAlreadyHasOrganizationException::forOwner($command->ownerUserId);
        }

        $now = $this->clock->now();
        $baseSlug = $command->slug ?? OrganizationSlugGenerator::fromName($command->name);
        $slug = $this->uniqueSlug($baseSlug);

        $organization = Organization::create(
            OrganizationId::generate(),
            $command->name,
            $slug,
            $command->ownerUserId,
            $now,
        );

        $this->organizations->save($organization);

        return $organization;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        while ($this->organizations->existsSlug($slug)) {
            $slug = OrganizationSlugGenerator::withRandomSuffix($base);
        }

        return $slug;
    }
}
