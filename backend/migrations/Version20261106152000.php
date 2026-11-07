<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[workspaces] create workspaces schema with workspaces, workspace_members, invitations tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS workspaces');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workspaces.workspaces (
                id UUID NOT NULL,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(120) NOT NULL,
                icon_color VARCHAR(20) NOT NULL,
                icon_initials VARCHAR(3) NOT NULL,
                owner_id UUID NOT NULL,
                created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_workspaces_workspaces_slug ON workspaces.workspaces (slug)',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workspaces.workspace_members (
                id UUID NOT NULL,
                workspace_id UUID NOT NULL,
                user_id UUID NOT NULL,
                role VARCHAR(20) NOT NULL,
                invited_by UUID DEFAULT NULL,
                joined_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_workspaces_members_ws_user'
            . ' ON workspaces.workspace_members (workspace_id, user_id)',
        );

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS workspaces.invitations (
                id UUID NOT NULL,
                workspace_id UUID NOT NULL,
                email VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                invited_by UUID NOT NULL,
                accepted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        SQL);
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_workspaces_invitations_token'
            . ' ON workspaces.invitations (token)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS workspaces.uniq_workspaces_invitations_token');
        $this->addSql('DROP TABLE IF EXISTS workspaces.invitations');
        $this->addSql('DROP INDEX IF EXISTS workspaces.uniq_workspaces_members_ws_user');
        $this->addSql('DROP TABLE IF EXISTS workspaces.workspace_members');
        $this->addSql('DROP INDEX IF EXISTS workspaces.uniq_workspaces_workspaces_slug');
        $this->addSql('DROP TABLE IF EXISTS workspaces.workspaces');
    }
}
