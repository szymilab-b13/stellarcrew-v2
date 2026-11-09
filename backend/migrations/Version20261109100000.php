<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[projects] create projects schema with projects table and partial unique (workspace_id, slug)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS projects');

        $this->addSql(<<<'SQL'
                CREATE TABLE IF NOT EXISTS projects.projects (
                    id UUID NOT NULL,
                    workspace_id UUID NOT NULL,
                    name VARCHAR(120) NOT NULL,
                    slug VARCHAR(120) NOT NULL,
                    description VARCHAR(500) DEFAULT NULL,
                    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                    PRIMARY KEY (id)
                )
            SQL);

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_projects_projects_workspace_id'
            . ' ON projects.projects (workspace_id)',
        );

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_projects_projects_ws_slug_active'
            . ' ON projects.projects (workspace_id, slug) WHERE deleted_at IS NULL',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS projects.uniq_projects_projects_ws_slug_active');
        $this->addSql('DROP INDEX IF EXISTS projects.idx_projects_projects_workspace_id');
        $this->addSql('DROP TABLE IF EXISTS projects.projects');
    }
}
