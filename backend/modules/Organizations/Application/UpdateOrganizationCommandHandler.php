<?php

declare(strict_types=1);

namespace Modules\Organizations\Application;

use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Organizations\Domain\OrganizationSlugGenerator;
use Modules\Shared\Kernel\Clock;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class UpdateOrganizationCommandHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
        private Clock $clock,
    ) {
    }

    public function __invoke(UpdateOrganizationCommand $command): Organization
    {
        $organization = $this->organizations->findById($command->organizationId);
        if (!$organization instanceof Organization || $organization->isDeleted()) {
            throw new NotFoundHttpException(
                sprintf('Organization "%s" not found.', (string) $command->organizationId),
            );
        }

        if (!$organization->ownerUserId()->equals($command->actorId)) {
            throw new AccessDeniedException(
                sprintf('Cannot update organization "%s".', (string) $command->organizationId),
            );
        }

        $now = $this->clock->now();

        if ($command->name !== null) {
            $organization->rename($command->name, $now);
        }

        if ($command->slug !== null && $command->slug !== $organization->slug()) {
            $organization->changeSlug($this->uniqueSlug($command->slug), $now);
        }

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
