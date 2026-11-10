<?php

declare(strict_types=1);

namespace Modules\Organizations\Application;

use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\Organizations\Domain\OrganizationSlugGenerator;
use Modules\Organizations\Domain\OwnerAlreadyHasOrganizationException;
use Modules\Shared\Kernel\Clock;
use Modules\Shared\Kernel\OrganizationId;

final readonly class CreateOrganizationCommandHandler
{
    public const string AUDIT_ACTION = 'organizations.organization.created';

    public const string AUDIT_RESOURCE_TYPE = 'organization';

    public function __construct(
        private OrganizationRepository $organizations,
        private AuditLoggerInterface $audit,
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

        $this->audit->log(new AuditEntry(
            userId: $command->ownerUserId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $organization->id(),
            metadata: [
                'after_state' => self::snapshot($organization),
            ],
        ));

        return $organization;
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Organization $organization): array
    {
        return [
            'id' => (string) $organization->id(),
            'name' => $organization->name(),
            'slug' => $organization->slug(),
            'owner_user_id' => (string) $organization->ownerUserId(),
        ];
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
