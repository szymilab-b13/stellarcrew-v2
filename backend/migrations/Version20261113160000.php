<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261113160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] create agents schema with agents table and partial unique index on (organization_id, slug)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS agents');

        $this->addSql(<<<'SQL'
                CREATE TABLE IF NOT EXISTS agents.agents (
                    id UUID NOT NULL,
                    organization_id UUID NOT NULL,
                    owner_user_id UUID NOT NULL,
                    source_template_id UUID DEFAULT NULL,
                    slug VARCHAR(80) NOT NULL,
                    name VARCHAR(80) NOT NULL,
                    role VARCHAR(120) NOT NULL,
                    instructions TEXT NOT NULL,
                    is_active BOOLEAN NOT NULL,
                    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL,
                    PRIMARY KEY (id)
                )
            SQL);

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_agents_agents_organization_id_slug_active'
            . ' ON agents.agents (organization_id, slug) WHERE deleted_at IS NULL',
        );

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_agents_agents_organization_id ON agents.agents (organization_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_agents_agents_owner_user_id ON agents.agents (owner_user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_agents_agents_source_template_id ON agents.agents (source_template_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS agents.idx_agents_agents_source_template_id');
        $this->addSql('DROP INDEX IF EXISTS agents.idx_agents_agents_owner_user_id');
        $this->addSql('DROP INDEX IF EXISTS agents.idx_agents_agents_organization_id');
        $this->addSql('DROP INDEX IF EXISTS agents.uniq_agents_agents_organization_id_slug_active');
        $this->addSql('DROP TABLE IF EXISTS agents.agents');
    }
}
