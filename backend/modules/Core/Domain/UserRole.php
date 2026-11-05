<?php

declare(strict_types=1);

namespace Modules\Core\Domain;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
