<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[agents] create agent_templates table with unique slug index';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
                CREATE TABLE IF NOT EXISTS agents.agent_templates (
                    id UUID NOT NULL,
                    slug VARCHAR(100) NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    role VARCHAR(120) NOT NULL,
                    role_description TEXT NOT NULL,
                    expertise TEXT NOT NULL,
                    workflow TEXT NOT NULL,
                    style TEXT NOT NULL,
                    output_format TEXT NOT NULL,
                    created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
                    PRIMARY KEY (id)
                )
            SQL);

        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_agents_agent_templates_slug ON agents.agent_templates (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS agents.uniq_agents_agent_templates_slug');
        $this->addSql('DROP TABLE IF EXISTS agents.agent_templates');
    }
}
