<?php

declare(strict_types=1);

namespace Modules\Core\Application\Install;

use DateTimeImmutable;

use function in_array;
use function json_encode;

use Modules\Auth\Api\AuthApi;
use Modules\Core\Domain\SystemSetting;
use Modules\Core\Domain\SystemSettingRepository;
use Modules\Shared\Kernel\Clock;

use function strlen;
use function trim;

final readonly class InstallSystemCommandHandler
{
    public const int MIN_PASSWORD_LENGTH = 10;

    public const string KEY_INSTANCE_NAME = 'branding.instance_name';

    public const string KEY_LOCALE = 'branding.locale';

    public const string KEY_LOGO_PATH = 'branding.logo_path';

    public const string KEY_WORKSPACE_NAME = 'workspace.first_name';

    public const string KEY_WORKSPACE_AGENTS = 'workspace.first_agents';

    private const array SUPPORTED_LOCALES = ['en', 'pl'];

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
        $this->validateLocale($command->locale);

        $this->auth->createAdmin(
            $command->adminEmail,
            $command->adminPlaintextPassword,
            $command->adminDisplayName,
        );

        $now = $this->clock->now();

        $this->saveSetting(self::KEY_INSTANCE_NAME, $this->normalise($command->instanceName), $now);
        $this->saveSetting(self::KEY_LOCALE, $command->locale, $now);
        $this->saveSetting(self::KEY_LOGO_PATH, $this->normalise($command->logoRelativePath), $now);
        $this->saveSetting(self::KEY_WORKSPACE_NAME, $this->normalise($command->workspaceName), $now);
        $this->saveSetting(
            self::KEY_WORKSPACE_AGENTS,
            json_encode($command->selectedAgentSlugs, JSON_THROW_ON_ERROR),
            $now,
        );

        $this->settings->save(new SystemSetting(
            IsInstalledQueryHandler::FLAG_NAME,
            IsInstalledQueryHandler::TRUE_VALUE,
            $now,
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

    private function validateLocale(string $locale): void
    {
        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            throw InvalidInstallInputException::unsupportedLocale($locale);
        }
    }

    private function saveSetting(string $name, ?string $value, DateTimeImmutable $when): void
    {
        if ($value === null) {
            return;
        }

        $this->settings->save(new SystemSetting($name, $value, $when));
    }

    private function normalise(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
