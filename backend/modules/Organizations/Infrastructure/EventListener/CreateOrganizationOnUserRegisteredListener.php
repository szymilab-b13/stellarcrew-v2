<?php

declare(strict_types=1);

namespace Modules\Organizations\Infrastructure\EventListener;

use function explode;

use Modules\Organizations\Application\CreateOrganizationCommand;
use Modules\Organizations\Application\CreateOrganizationCommandHandler;
use Modules\Organizations\Domain\Organization;
use Modules\Organizations\Domain\OrganizationRepository;
use Modules\UserManagement\Api\Event\UserRegistered;

use function sprintf;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function trim;

#[AsEventListener(event: UserRegistered::class)]
final readonly class CreateOrganizationOnUserRegisteredListener
{
    public function __construct(
        private OrganizationRepository $organizations,
        private CreateOrganizationCommandHandler $create,
    ) {
    }

    public function __invoke(UserRegistered $event): void
    {
        if ($this->organizations->findByOwner($event->userId) instanceof Organization) {
            return;
        }

        $seed = $this->nameSeed($event->displayName, $event->email);

        ($this->create)(new CreateOrganizationCommand(
            ownerUserId: $event->userId,
            name: sprintf("%s's Organization", $seed),
        ));
    }

    private function nameSeed(?string $displayName, string $email): string
    {
        $trimmed = $displayName !== null ? trim($displayName) : '';
        if ($trimmed !== '') {
            return $trimmed;
        }

        $local = explode('@', $email, 2)[0];

        return $local !== '' ? $local : 'User';
    }
}
