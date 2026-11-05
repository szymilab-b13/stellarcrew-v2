<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Console;

use Modules\Core\Application\Install\InstallSystemCommand;
use Modules\Core\Application\Install\InstallSystemCommandHandler;
use Modules\Core\Application\Install\IsInstalledQueryHandler;
use Modules\Core\Application\Install\SystemAlreadyInstalledException;
use RuntimeException;

use function sprintf;
use function strlen;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'stellarcrew:install', description: 'Seed the first admin and mark the system as installed.')]
final class InstallCommand extends Command
{
    public function __construct(
        private readonly IsInstalledQueryHandler $isInstalled,
        private readonly InstallSystemCommandHandler $install,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (($this->isInstalled)()) {
            $io->error('System is already installed.');

            return Command::FAILURE;
        }

        $email = (string) $io->ask('Admin email', null, function (?string $value): string {
            $value = (string) $value;
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('Invalid email address.');
            }

            return $value;
        });

        $password = $this->askPassword($io);
        $displayName = $io->ask('Display name (how should we address the admin?)');

        try {
            ($this->install)(new InstallSystemCommand(
                adminEmail: $email,
                adminPlaintextPassword: $password,
                adminDisplayName: $displayName === null ? null : (string) $displayName,
            ));
        } catch (SystemAlreadyInstalledException $systemAlreadyInstalledException) {
            $io->error($systemAlreadyInstalledException->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Admin %s created. System is installed.', $email));

        return Command::SUCCESS;
    }

    private function askPassword(SymfonyStyle $io): string
    {
        $question = (new Question('Admin password (min 10 chars)'))
            ->setHidden(true)
            ->setMaxAttempts(3)
            ->setValidator(static function (?string $value): string {
                $value = (string) $value;
                if (strlen($value) < 10) {
                    throw new RuntimeException('Password must be at least 10 characters long.');
                }

                return $value;
            });

        $confirmation = (new Question('Confirm admin password'))
            ->setHidden(true)
            ->setMaxAttempts(3);

        $password = (string) $io->askQuestion($question);
        $confirmed = (string) $io->askQuestion($confirmation);

        if ($password !== $confirmed) {
            throw new RuntimeException('Passwords do not match.');
        }

        return $password;
    }
}
