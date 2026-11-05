<?php

declare(strict_types=1);

namespace Modules\Auth\Domain;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
