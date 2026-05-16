<?php

declare(strict_types=1);

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Editor => 'Editor',
            self::Viewer => 'Viewer',
        };
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canEdit(): bool
    {
        return $this !== self::Viewer;
    }

    public function canDelete(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canManageSettings(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
