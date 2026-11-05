<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

use Modules\Auth\Api\AuthApi;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Shared\Kernel\Clock;

use function strlen;

final readonly class InstallSystemCommandHandler
{
    public const int MIN_PASSWORD_LENGTH = 10;

    public function __construct(
        private AuthApi $auth,
        private SystemSettingRepository $settings,
        private Clock $clock,
    ) {
    }

    public function __invoke(InstallSystemCommand $command): void
    {
        $this->guardNotInstalled();
        $this->validateEmail($command->adminEmail);
        $this->validatePassword($command->adminPlaintextPassword);

        $this->auth->createAdmin($command->adminEmail, $command->adminPlaintextPassword);

        $this->settings->save(new SystemSetting(
            IsInstalledQueryHandler::FLAG_NAME,
            IsInstalledQueryHandler::TRUE_VALUE,
            $this->clock->now(),
        ));
    }

    private function guardNotInstalled(): void
    {
        $flag = $this->settings->get(IsInstalledQueryHandler::FLAG_NAME);
        if ($flag instanceof SystemSetting && $flag->value() === IsInstalledQueryHandler::TRUE_VALUE) {
            throw SystemAlreadyInstalledException::create();
        }
    }

    private function validateEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidInstallInputException::invalidEmail($email);
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw InvalidInstallInputException::passwordTooShort(self::MIN_PASSWORD_LENGTH);
        }
    }
}
