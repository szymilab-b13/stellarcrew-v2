<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261112150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workspaces] enforce organization_id NOT NULL + add foreign key to organizations.organizations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                UPDATE workspaces.workspaces w
                SET organization_id = o.id
                FROM organizations.organizations o
                WHERE o.owner_user_id = w.owner_id
                  AND o.deleted_at IS NULL
                  AND w.organization_id IS NULL
            SQL);

        $this->addSql('ALTER TABLE workspaces.workspaces ALTER COLUMN organization_id SET NOT NULL');

        $this->addSql(<<<'SQL'
                ALTER TABLE workspaces.workspaces
                ADD CONSTRAINT fk_workspaces_workspaces_organization_id
                FOREIGN KEY (organization_id)
                REFERENCES organizations.organizations (id)
                ON DELETE RESTRICT
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspaces.workspaces DROP CONSTRAINT IF EXISTS fk_workspaces_workspaces_organization_id');
        $this->addSql('ALTER TABLE workspaces.workspaces ALTER COLUMN organization_id DROP NOT NULL');
    }
}
