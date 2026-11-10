<?php

declare(strict_types=1);

namespace Modules\Organizations\Application\Query;

use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;

use function sprintf;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GetMyOrganizationQueryHandler
{
    public function __construct(
        private OrganizationRepository $organizations,
    ) {
    }

    public function __invoke(GetMyOrganizationQuery $query): Organization
    {
        $organization = $this->organizations->findByOwner($query->actorId);

        if (!$organization instanceof Organization) {
            throw new NotFoundHttpException(
                sprintf('User "%s" does not own an organization.', (string) $query->actorId),
            );
        }

        return $organization;
    }
}
