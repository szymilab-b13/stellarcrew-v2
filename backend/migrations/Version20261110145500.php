<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110145500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workspaces] add nullable organization_id column + backfill organizations.organizations and workspaces.organization_id per unique owner';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces.workspaces ADD COLUMN IF NOT EXISTS organization_id UUID DEFAULT NULL');

        $this->addSql(<<<'SQL'
                INSERT INTO organizations.organizations (id, name, slug, owner_user_id, created_at, updated_at)
                SELECT
                    gen_random_uuid(),
                    COALESCE(up.display_name, SPLIT_PART(au.email, '@', 1)) || '''s Organization',
                    'user-' || SUBSTR(REPLACE(au.id::text, '-', ''), 1, 12),
                    au.id,
                    NOW(),
                    NOW()
                FROM (
                    SELECT DISTINCT owner_id
                    FROM workspaces.workspaces
                    WHERE organization_id IS NULL
                ) distinct_owners
                JOIN auth.users au ON au.id = distinct_owners.owner_id
                LEFT JOIN user_management.user_profiles up ON up.id = au.id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM organizations.organizations existing
                    WHERE existing.owner_user_id = au.id
                      AND existing.deleted_at IS NULL
                )
            SQL);

        $this->addSql(<<<'SQL'
                UPDATE workspaces.workspaces w
                SET organization_id = o.id
                FROM organizations.organizations o
                WHERE o.owner_user_id = w.owner_id
                  AND o.deleted_at IS NULL
                  AND w.organization_id IS NULL
            SQL);

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_workspaces_workspaces_organization_id'
            . ' ON workspaces.workspaces (organization_id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS workspaces.idx_workspaces_workspaces_organization_id');
        $this->addSql('ALTER TABLE workspaces.workspaces DROP COLUMN IF EXISTS organization_id');
    }
}
