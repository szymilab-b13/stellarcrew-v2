<?php

declare(strict_types=1);

namespace Modules\Workspaces\Domain;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Guest = 'guest';

    public function canEditWorkspace(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member, self::Guest => false,
        };
    }

    public function canDeleteWorkspace(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member, self::Guest => false,
        };
    }

    public function canChangeRoles(): bool
    {
        return $this === self::Owner;
    }

    public function canCreateProjects(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Member => true,
            self::Guest => false,
        };
    }

    public function canViewProjects(): bool
    {
        return true;
    }

    public function canReadMessages(): bool
    {
        return true;
    }
}
