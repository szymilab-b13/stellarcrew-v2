<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\UserManagement;

use function array_values;
use function count;

use Modules\Shared\Kernel\UserId;
use Modules\UserManagement\Domain\UserProfile;
use Modules\UserManagement\Domain\UserProfileRepository;

final class InMemoryUserProfileRepository implements UserProfileRepository
{
    /** @var array<string, UserProfile> */
    private array $profiles = [];

    public function save(UserProfile $profile): void
    {
        $this->profiles[(string) $profile->id()] = $profile;
    }

    public function findById(UserId $id): ?UserProfile
    {
        return $this->profiles[(string) $id] ?? null;
    }

    public function count(): int
    {
        return count($this->profiles);
    }

    /**
     * @return list<UserProfile>
     */
    public function all(): array
    {
        return array_values($this->profiles);
    }
}
