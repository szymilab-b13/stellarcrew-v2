<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

use Modules\Auth\Api\AuthApi;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

final readonly class DeactivateUserCommandHandler
{
    public const string AUDIT_ACTION = 'user_management.user.deactivated';

    public const string AUDIT_RESOURCE_TYPE = 'user';

    public function __construct(
        private UserProfileRepository $profiles,
        private AuthApi $auth,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(DeactivateUserCommand $command): void
    {
        $profile = $this->profiles->findById($command->userId);
        if (!$profile instanceof UserProfile) {
            throw UserProfileNotFoundException::forId($command->userId);
        }

        if ($profile->isDeactivated()) {
            return;
        }

        $this->auth->markDeactivated($command->userId);

        $now = $this->clock->now();
        $profile->deactivate($now);
        $this->profiles->save($profile);

        $this->audit->log(new AuditEntry(
            userId: $command->actorUserId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $command->userId,
        ));
    }
}
