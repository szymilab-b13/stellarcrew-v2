<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

use Modules\Auth\Api\AuthApi;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\UserManagement\Api\Dto\UserProfileDto;
use Modules\UserManagement\Api\Event\UserRegistered;
use Modules\UserManagement\Domain\AvatarColorPicker;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;
use Psr\EventDispatcher\EventDispatcherInterface;

final readonly class CreateUserCommandHandler
{
    public const string AUDIT_ACTION = 'user_management.user.created';

    public const string AUDIT_RESOURCE_TYPE = 'user';

    public function __construct(
        private AuthApi $auth,
        private UserProfileRepository $profiles,
        private AuditLoggerInterface $audit,
        private Clock $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    public function __invoke(CreateUserCommand $command): UserProfileDto
    {
        $authUser = $this->auth->createUser(
            $command->email,
            $command->plaintextPassword,
            $command->isSuperAdmin,
            $command->displayName,
        );

        $now = $this->clock->now();
        $profile = UserProfile::create(
            $authUser->id,
            $command->displayName,
            AvatarColorPicker::pick($command->email),
            $now,
        );

        $this->profiles->save($profile);

        $this->audit->log(new AuditEntry(
            userId: $command->actorUserId,
            action: self::AUDIT_ACTION,
            occurredAt: $now,
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $profile->id(),
            metadata: [
                'email' => $command->email,
                'is_super_admin' => $command->isSuperAdmin,
            ],
        ));

        $this->events->dispatch(new UserRegistered(
            userId: $authUser->id,
            email: $command->email,
            displayName: $command->displayName,
            occurredAt: $now,
        ));

        return new UserProfileDto(
            id: (string) $profile->id(),
            displayName: $profile->displayName(),
            avatarColor: $profile->avatarColor(),
            isDeactivated: $profile->isDeactivated(),
        );
    }
}
