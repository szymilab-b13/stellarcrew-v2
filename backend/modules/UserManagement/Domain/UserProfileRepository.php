<?php

declare(strict_types=1);

namespace Modules\UserManagement\Domain;

use Modules\Shared\Kernel\UserId;

interface UserProfileRepository
{
    public function save(UserProfile $profile): void;

    public function findById(UserId $id): ?UserProfile;

    public function count(): int;
}
