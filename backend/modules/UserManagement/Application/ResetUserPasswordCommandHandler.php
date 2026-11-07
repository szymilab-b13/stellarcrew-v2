<?php

declare(strict_types=1);

namespace Modules\UserManagement\Application;

use Modules\Auth\Api\AuthApi;
use Modules\Core\Api\AuditLoggerInterface;
use Modules\Core\Api\Dto\AuditEntry;
use Modules\Shared\Kernel\Clock;
use Modules\UserManagement\Api\Exception\PasswordTooShortException;
use Modules\UserManagement\Api\Exception\UserProfileNotFoundException;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

use function strlen;

final readonly class ResetUserPasswordCommandHandler
{
    public const string AUDIT_ACTION = 'user_management.user.password_reset';

    public const string AUDIT_RESOURCE_TYPE = 'user';

    public const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private UserProfileRepository $profiles,
        private AuthApi $auth,
        private AuditLoggerInterface $audit,
        private Clock $clock,
    ) {
    }

    public function __invoke(ResetUserPasswordCommand $command): void
    {
        $profile = $this->profiles->findById($command->userId);
        if (!$profile instanceof UserProfile) {
            throw UserProfileNotFoundException::forId($command->userId);
        }

        if (strlen($command->newPassword) < self::MIN_PASSWORD_LENGTH) {
            throw PasswordTooShortException::minimumLength(self::MIN_PASSWORD_LENGTH);
        }

        $this->auth->setPassword($command->userId, $command->newPassword);

        $this->audit->log(new AuditEntry(
            userId: null,
            action: self::AUDIT_ACTION,
            occurredAt: $this->clock->now(),
            resourceType: self::AUDIT_RESOURCE_TYPE,
            resourceId: (string) $command->userId,
        ));
    }
}
